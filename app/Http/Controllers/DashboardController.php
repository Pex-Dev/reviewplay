<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public static function index(Request $request)
    {
        //Obtener los juegos mejor calificados
        $highestScoreGames = Game::withAvg('reviews', 'score')->orderByDesc('reviews_avg_score')->latest()->take(6)->get();

        //Obtener las ultimas reseñas
        $latestReviews = Review::with(['game', 'user'])->where('user_id', '!=', 1)->latest()->take(6)->get();

        $users = User::withCount(['reviews', 'favoriteGames', 'followers', 'followedGames'])->get();
        //Usuarios destacados (Promedio entre reseñas, favoritos, juegos seguidos y seguidores)
        $topUsers = self::getTopUsers($users);

        return response()->json([
            'games' => $highestScoreGames,
            'latestReviews' => $latestReviews,
            'topUsers' => $topUsers
        ]);
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
