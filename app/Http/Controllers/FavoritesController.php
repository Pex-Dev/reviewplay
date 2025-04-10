<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Game;
use App\Models\User;
use App\Services\GameService;
use Illuminate\Http\Request;

class FavoritesController extends Controller
{
    public static function index(Request $request)
    {
        $user = User::find($request['id']);

        //Retornar si no se encuentra el usuario.
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontro el usuario'
            ], 404);
        }

        //Buscar favoritos del usuario y ordenarlos por fecha
        $favorites = $user->favoriteGames()->paginate(20);

        //Retornar respuesta
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name
            ],
            'favorites' => $favorites
        ]);
    }

    public static function store(Request $request)
    {

        //Buscar juego
        $game = Game::find($request['id']);

        //Retornar respuesta 
        if (!$game) {
            //Añadir juego a la base de datos

            return response()->json([
                'success' => false,
                'message' => 'No se pudo encontrar el juego'
            ], 404);
        }

        //Registrar juego
        Favorite::create([
            'user_id' => auth()->user()->id,
            'game_id' => $game->id,
        ]);

        //Retornar respuesta
        return response()->json([
            'success' => true,
            'message' => 'Agregado a favoritos correctamente'
        ]);
    }

    public static function addToFavorites(Request $request)
    {
        //Obtener id del juego
        $id = $request['id'] ?? null;

        //Ver si el juego ya esta en favoritos del usuario
        $favoriteGame = auth()->user()->favoriteGames()->where('games.id', $id)->exists();

        //Si esta en favoritos retornar
        if ($favoriteGame) {
            return response()->json([
                'message' => 'Ya esta registrado como favorito'
            ], 409);
        }

        //Si el usuario es invitado no puede agregar mas de 5 juegos a favoritos
        if (auth()->user()->id == 1) {
            if (auth()->user()->favoriteGames()->count() >= 5) {
                return response()->json([
                    'message' => 'No puedes agregar mas de 5 juegos a favoritos'
                ], 409);
            }
        }

        //Buscar juego en la base de datos
        $game = Game::where('id', $id)->first();

        //Si el juego no esta en la base de datos hay que registrarlo
        if (!$game) {
            //Buscar juego en la API RAWG
            $game = GameService::getGameAPI($id);

            //Si no se encontro el juego
            if (!$game) {
                return response()->json([
                    'message' => 'No se pudo encontrar el juego'
                ], 404);
            }

            //Almacenar en la base de datoss
            $gameDB = Game::create([
                'id' => $game['id'],
                'name' => $game['name'],
                'alternative_names' => count($game['alternative_names']) > 0 ? implode(" ", $game['alternative_names']) : null,
                'description' => $game['description'],
                'developers' => json_encode($game['developers']),
                'background_image' => $game['background_image'],
                'released' => $game['released'],
                'genres' => json_encode($game['genres']),
                'tags' => json_encode($game['tags']),
                'platforms' => json_encode($game['platforms'])
            ]);

            //Id que se usara para referenciar el juego en favoritos
            $gameId = $gameDB->id;
        } else {
            //Id que se usara para referenciar el juego en favoritos
            $gameId = $game->id;
        }

        //Registrar juego en favoritos
        Favorite::create([
            'user_id' => auth()->user()->id,
            'game_id' => $gameId,
        ]);

        //Retornar respuesta
        return response()->json([
            'success' => true,
            'message' => 'Agregado a favoritos correctamente'
        ]);
    }

    public static function removeFromFavorites(Request $request)
    {
        $id = $request['id'] ?? null;

        //Ver si el juego ya esta en favoritos del usuario
        $favoriteGame = auth()->user()->favoriteGames()->where('games.id', $id)->first();

        //Si el juego no esta en los favoritos retornamos
        if (!$favoriteGame) {
            return response()->json([
                'success' => false,
                'message' => 'El juego no esta registrado como favoritos del usuario'
            ], 404);
        }

        //Eliminar el juego de favoritos
        auth()->user()->favoriteGames()->detach($favoriteGame->id);

        return response()->json([
            'success' => true,
            'message' => 'El juego se elimino de favoritos correctamente'
        ]);
    }
}
