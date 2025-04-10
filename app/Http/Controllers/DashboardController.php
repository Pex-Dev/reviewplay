<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public static function index()
    {
        //Obtener los juegos mejor calificados
        $highestScoreGames = Game::withAvg('reviews', 'score')->orderByDesc('reviews_avg_score')->latest()->take(6)->get();

        //Obtener las ultimas reseñas
        $latestReviews = Review::with(['game', 'user'])->where('user_id', '!=', 1)->latest()->take(6)->get();

        //Obtener usuarios con mayor cantidad de reseñas
        $topUsers = User::withCount(['reviews', 'favoriteGames'])->orderby('reviews_count', 'desc')->limit(4)->get();

        return response()->json([
            'games' => $highestScoreGames,
            'latestReviews' => $latestReviews,
            'topUsers' => $topUsers
        ]);
    }
}
