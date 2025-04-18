<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public static function index(Request $request)
    {
        $order = $request->input('order') === 'desc' ? 'desc' : 'asc';

        //Obtener notificaciones
        $notifications = DatabaseNotification::where('notifiable_id', $request->user()->id)
            ->where('notifiable_type', \App\Models\User::class)
            ->orderBy('created_at', $order)
            ->paginate(20);


        //Obtener ids de los usuarios de las notificaciones
        $usersId = collect($notifications->items())
            ->pluck('data.user_id')
            ->unique()
            ->filter()
            ->all();

        //Obtener usuario a partir de los ids de las notificaciones
        $users = \App\Models\User::whereIn('id', $usersId)
            ->select('id', 'name', 'image')
            ->get()
            ->keyBy('id');


        return response()->json([
            'notifications' => $notifications,
            'users' => $users,
        ]);
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
