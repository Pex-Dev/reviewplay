<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class ComunityController extends Controller
{
    public static function index(Request $request)
    {
        $users = User::withCount(['reviews', 'favoriteGames', 'followers', 'followedGames'])->get();

        //Usuarios destacados (Promedio entre reseñas, favoritos, juegos seguidos y seguidores)
        $topUsers = self::getTopUsers($users);

        //Usuarios con mas seguidores
        $topUsersFollowers = User::withCount(['reviews', 'favoriteGames', 'followers', 'followedGames'])->orderby('followers_count', 'desc')->limit(4)->get();

        //Usuarios con mas reseñas
        $topUsersReviews = User::withCount(['reviews', 'favoriteGames', 'followers', 'followedGames'])->orderby('reviews_count', 'desc')->limit(4)->get();

        return response()->json([
            'topUsers' => $topUsers,
            'topUsersFollowers' => $topUsersFollowers,
            'topUsersReviews' => $topUsersReviews
        ]);
    }

    public static function searchUsers(Request $request)
    {
        $name = $request['name'];

        //Buscar usuarios
        $users = User::select(['id', 'name', 'created_at', 'image'])->where('name', 'LIKE', "%" . $name . "%")
            ->where('id', '!=', 1)->withCount(['reviews', 'favoriteGames', 'followers', 'followedGames'])->paginate(10);

        return $users;
    }

    private static function getTopUsers($users)
    {
        return $users->map(function ($user) {
            $activityScore = (
                $user->reviews_count * 2 +
                $user->favorite_games_count * 1 +
                $user->followers_count * 1.5 +
                $user->followed_games_count * 1
            );

            $user->activity_score = $activityScore;

            return $user;
        })->sortByDesc('activity_score')->take(4)->values()->toArray();
    }
}
