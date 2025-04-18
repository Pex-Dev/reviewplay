<?php

use App\Http\Controllers\ComunityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FavoritesController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\GamesController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;


Route::get('/dashboard', [DashboardController::class, "index"]);

//Usuario
Route::middleware(['auth:sanctum'])->get('/users', [UserController::class, 'show']);



//Comunidad
Route::get('/community', [ComunityController::class, 'index']);
Route::get('/community/users/search/{name}', [ComunityController::class, 'searchUsers']);

//Perfil
Route::get('/profile/{id}', [ProfileController::class, "show"]);
Route::middleware('auth:sanctum')->group(function () {
    Route::put('/profile/{id}', [ProfileController::class, "update"]);
    Route::put('/profile/{id}/updatePassword', [ProfileController::class, "updatePassword"]);
});


//Juegos
Route::get('/games', [GamesController::class, 'index']);
Route::get('/games/{id}', [GamesController::class, 'show']);

//Reseñas
Route::get('/reviews', [ReviewsController::class, 'index']);
Route::get('/reviews/{id}', [ReviewsController::class, 'show']);
Route::get('/users/{id}/reviews', [ReviewsController::class, "userReviews"]);
Route::get('/games/{id}/reviews', [ReviewsController::class, 'gameReviews']);
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::post('/reviews', [ReviewsController::class, 'store']);
    Route::put('/reviews', [ReviewsController::class, 'update']);
    Route::delete('/reviews', [ReviewsController::class, 'destroy']);
});

//Juegos favoritos
Route::get('/users/{id}/favorites', [FavoritesController::class, "index"]);
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::post('/favorites', [FavoritesController::class, 'store']);
    Route::delete('/favorites', [FavoritesController::class, 'destroy']);
});

//Seguir usuario y juegos
Route::get('/users/{id}/followed', [FollowController::class, 'getFollowed']);
Route::get('/users/{id}/followers', [FollowController::class, 'getFollowers']);
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/users/{id}/follow', [FollowController::class, 'followUser']);
    Route::delete('/users/{id}/unfollow', [FollowController::class, 'unfollowUser']);
    Route::post('/games/{id}/follow', [FollowController::class, 'followGame']);
    Route::delete('/games/{id}/unfollow', [FollowController::class, 'unfollowGame']);
});

//Notificaciones
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/mark-visible-as-read', [NotificationController::class, 'update']);
});
