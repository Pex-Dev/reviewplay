<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke($id, $hash)
    {
        $user = User::find($id);
        if (!$user) {
            return redirect()->to(env('FRONTEND_URL') . '/login');
        }

        // Verificar si el hash coincide y si no redireccionar
        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return redirect()->to(env('FRONTEND_URL') . '/verify-email?status=invalid');
        }

        // Marcar al usuario como ya verificado
        if ($user->hasVerifiedEmail()) {
            return redirect()->to(env('FRONTEND_URL') . '/verify-email?status=already-verified');
        }

        //Verificar usuario
        $user->markEmailAsVerified();


        // Redirigir al frontend con un mensaje de éxito
        return redirect()->to(env('FRONTEND_URL') . '/verify-email?status=verified');
    }
}
