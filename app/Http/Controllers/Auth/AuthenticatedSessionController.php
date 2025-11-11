<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cookie;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {
        // Validar los datos de entrada
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Intentar autenticar al usuario con su email y password
        if (Auth::attempt($request->only('email', 'password'), ($request->has('remember') && $request['remember'] == true))) {
            $user = Auth::user();

            //Verificar que el usuario este verificado.
            if (!$user->email_verified_at) {

                //Eliminar token de acceso del usuario
                $user->tokens->each(function ($token) {
                    $token->delete();
                });

                $request->session()->invalidate();
                $request->session()->regenerateToken();
                Auth::guard('web')->logout();

                return response()->json([
                    'success' => false,
                    'verified' => false,
                    'errors' => ['message' => 'Usuario no verificado']
                ], 403);
            }

            $unreadNotifications = $user->unreadNotifications->count();

            $response = [
                'user' => $user,
                'unreadNotifications' => $unreadNotifications,
                'success' => true,
                'verified' => true,
                'remember' => ($request->has('remember') && $request['remember'] == true),
                'message' => 'Sesión Iniciada'
            ];

            return response()->json($response);
        }

        // Si no se puede autenticar retornar mensaje
        return response()->json([
            'errors' => ['message' => 'Correo o contraseña incorrecta'],
            'verified' => true,
            'success' => false
        ], 401);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        //Obtener usuario
        $user = Auth::user();

        //Verificar si el usuario es invitado
        if ($user->id == 1) {
            //Reestablecer registros del usuario invitado
            $reviews = $user->reviews()->get();
            foreach ($reviews as $review) {
                $review->delete();
            }
            $favorites = $user->favoriteGames()->get();
            foreach ($favorites as $favorite) {
                $user->favoriteGames()->detach($favorite->id);
            }

            //Eliminar imagen si tiene una
            if ($user->image) {
                Storage::disk('public')->delete($user->image);
            }
            $user->image = null;
            $user->description = "Cuenta de invitado. Solo puede registrar 5 juegos como favoritos y 5 reseñas.";
            $user->save();
        }

        //Eliminar token de acceso del usuario
        $user->tokens->each(function ($token) {
            $token->delete();
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Auth::guard('web')->logout();

        // Forzar borrar cookie de sesión
        Cookie::queue(Cookie::forget('reviewplay_session_v2'));

        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }

    //Iniciar sesión en la cuente de invitado (guest)
    public function loginAsGuest(Request $request)
    {
        //Encontrar el usuario invitado
        $userGuest = User::find(1);

        //Verificar si el usuario invitado existe
        if (!$userGuest) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario invitado no encontrado'
            ], 404);
        }

        //Autenticar al usuario invitado
        Auth::login($userGuest);

        //Reestablecer registros del usuario invitado
        $reviews = $userGuest->reviews()->get();
        foreach ($reviews as $review) {
            $review->delete();
        }
        $favorites = $userGuest->favoriteGames()->get();
        foreach ($favorites as $favorite) {
            $userGuest->favoriteGames()->detach($favorite->id);
        }

        //Eliminar imagen si tiene una
        if ($userGuest->image) {
            Storage::disk('public')->delete($userGuest->image);
        }
        $userGuest->image = null;
        $userGuest->description = "Cuenta de invitado. Solo puede registrar 5 juegos como favoritos y 5 reseñas.";
        $userGuest->save();

        //retornar respuesta
        return response()->json(
            [
                'user' => $userGuest,
                'success' => true,
                'verified' => true,
                'remember' => false,
                'message' => 'Sesión Iniciada',
                'reviews' => $reviews,
            ]
        );
    }
}
