<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
        if (Auth::attempt($request->only('email', 'password'), $request->filled('remember'))) {
            $user = Auth::user();

            //Verificar que el usuario este verificado.
            if (!$user->email_verified_at) {

                //Cerrar sesión
                Auth::logout();
                $request -> session() -> invalidate();
                $request -> session() -> regenerateToken();

                return response()->json([
                    'success' => false,
                    'verified' => false,
                    'errors' => ['message' => 'Usuario no verificado']
                ], 403);
            }

            //Contar notificaciones pendientes
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
            
            // Reseñas
            $user->reviews()->delete();
            
            // Favoritos
            $user->favoriteGames()->detach();

            //Eliminar imagen si tiene una
            if ($user->image) {
                Storage::disk('public')->delete($user->image);
            }

            $user->update([
                'image' => null,
                'description' => "Cuenta de invitado. Solo puede registrar 5 juegos como favoritos y 5 reseñas."
            ]);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }

    //Iniciar sesión en la cuente de invitado (guest)
    public function loginAsGuest(Request $request)
    {
        //Encontrar el usuario invitado
        $userGuest = User::find(1);

        if (!$userGuest) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario invitado no encontrado'
            ], 404);
        }

        //Eliminar reseñas
        $userGuest->reviews()->delete();

        //Eliminar favoritos
        $userGuest->favoriteGames()->detach();

        //Eliminar imagen
        if ($userGuest->image) {
            Storage::disk('public')->delete($userGuest->image);
        }

        //Reiniciar descripción
        $userGuest->update([
            'image' => null,
            'description' => "Cuenta de invitado. Solo puede registrar 5 juegos como favoritos y 5 reseñas."
        ]);

        //Iniciar sesión
        Auth::login($userGuest);

        return response()->json([
            'user' => $userGuest,
            'success' => true,
            'verified' => true,
            'remember' => false,
            'message' => 'Sesión Iniciada'
        ]);
    }

}
