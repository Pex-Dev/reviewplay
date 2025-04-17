<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public static function index(Request $request)
    {
        $order = request('order') === 'desc' ? 'desc' : 'asc';

        //Uso DatabaseNotification en vez de user()->notifications() porque por alguna razón no funionaba el order by. 
        //Se hacia un order by dos veces en la consulta.

        return DatabaseNotification::where('notifiable_id', $request->user()->id)
            ->where('notifiable_type', 'App\Models\User')
            ->orderBy('created_at', $order)
            ->paginate(20);
    }

    public static function update(Request $request)
    {
        $request->validate([
            'ids' => ['required', 'array'], //Para el campo ids 
            'ids.*' => ['uuid'], //Para cada uno dentro del campo ids
        ]);

        $request->user()->unreadNotifications()->whereIn('id', $request['ids'])->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Notificaciones marcadas como leidas',
            'unreadNotificationsCount' => $request->user()->unreadNotifications->count()
        ]);
    }
}
