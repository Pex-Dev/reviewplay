<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PruebaController extends Controller
{
    public function index(){
        $games = [
            ['id' => 1, 'title' => 'Elden Ring', 'genre' => 'RPG'],
            ['id' => 2, 'title' => 'Bloodborne', 'genre' => 'Action RPG'],
            ['id' => 3, 'title' => 'Monster Hunter Wilds', 'genre' => 'Action'],
        ];

        return response()->json($games);
    }
}
