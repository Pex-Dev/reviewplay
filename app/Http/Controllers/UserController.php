<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public static function show(Request $request)
    {
        $user = auth()->user();
        $unreadNotifications = $user->unreadNotifications->count();

        return response()->json([
            'user' => $user,
            'unreadNotifications' => $unreadNotifications
        ]);
    }
}
