<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): JsonResponse
    {
        //Encontrar usuario por el email
        $user = User::where('email', $request['email'])->first();


        //si no se encontro el usuario
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "Usuario no encontrado"
            ], 404);
        }

        if ($user->hasVerifiedEmail()) {
            if (!$user) {
                return response()->json([
                    'success' => true,
                    'message' => "El usuario ya tiene su email verificado"
                ], 200);
            }
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => "Email de verificación enviado correctamente",
            'status' => 'verification-link-sent'
        ]);
    }
}
