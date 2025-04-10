<?php

use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FavoritesController;
use App\Http\Controllers\GamesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/dashboard', [DashboardController::class, "index"]);

Route::get('/profile/{id}', [ProfileController::class, "show"]);
Route::get('/profile/{id}/reviews', [ReviewsController::class, "getUserReviews"]);
Route::middleware(['auth:sanctum'])->put('/profile/{id}', [ProfileController::class, "update"]);
Route::middleware(['auth:sanctum'])->put('/profile/{id}/updatePassword', [ProfileController::class, "updatePassword"]);

Route::middleware(['auth:sanctum'])->post('/sendVerificationEmail', [EmailVerificationNotificationController::class, 'store']);

Route::get('/games', [GamesController::class, 'index']);
Route::post('/game', [GamesController::class, 'show']);
Route::get('/game/{id}/reviews', [ReviewsController::class, 'getGameReviews']);

Route::middleware(['auth:sanctum', 'verified'])->post('/addToFavorites', [FavoritesController::class, 'addToFavorites']);
Route::middleware(['auth:sanctum', 'verified'])->post('/removeFromFavorites', [FavoritesController::class, 'removeFromFavorites']);

Route::get('/reviews', [ReviewsController::class, 'index']);
Route::get('/getReview/{id}', [ReviewsController::class, 'show']);
Route::middleware(['auth:sanctum', 'verified'])->post('/addReview', [ReviewsController::class, 'store']);
Route::middleware(['auth:sanctum', 'verified'])->put('/updateReview', [ReviewsController::class, 'update']);
Route::middleware(['auth:sanctum', 'verified'])->delete('/deleteReview', [ReviewsController::class, 'destroy']);

Route::get('/favorites/{id}', [FavoritesController::class, "index"]);
