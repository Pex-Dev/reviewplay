<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\User;
use App\Notifications\NewFollowerNotification;
use App\Services\GameService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class FollowController extends Controller
{
    public static function followUser($userId)
    {
        $follower = auth()->user();

        //encontrar usuario a seguir
        $user = User::find($userId);

        //Retornar error si no se encontro el usuario
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo encontrar el usuario'
            ], 404);
        }

        //Verificar que el usuario a seguir no sea el mismo usuario
        if ($user->id === $follower->id) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes seguirte a ti mismo'
            ], 400);
        }

        //Verificar si ya sigue al usuario
        if ($user->followers()->where('follower_id', $follower->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ya sigues a este usuario'
            ], 400);
        }

        //Verificar que no sea el usuario invitado a quien quiere seguir
        if ($user->id == 1) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes seguir al usuario invitado'
            ], 400);
        }

        //Si el usuario es el invitado verificar que no pasae de 5 seguidos
        if ($follower->id == 1) {
            if ($follower->following()->count() >= 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes seguir mas de 5 usuarios'
                ], 403);
            }
        }

        //Crear relación de seguimiento
        $user->followers()->attach($follower->id);

        //Notificar al usuario de que tiene un nuevo seguidor
        $user->notify(new NewFollowerNotification($follower));

        //Retornar respuesta
        return response()->json([
            'success' => true,
            'message' => 'Ahora sigues a ' . $user->name,
        ]);
    }

    public static function unfollowUser($userId)
    {
        $follower = auth()->user();

        //encontrar usuario a seguir
        $user = User::find($userId);

        //Retornar error si no se encontro el usuario
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo encontrar el usuario'
            ], 404);
        }

        //Verificar que el usuario a dejar de seguir no sea el mismo
        if ($user->id === $follower->id) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes dejar de seguirte a ti mismo'
            ], 400);
        }

        //Verificar si se sigue al usuario
        if (!$user->followers()->where('follower_id', $follower->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No sigues sigues a este usuario'
            ], 400);
        }

        $follower->following()->detach($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Ya no sigues a ' . $user->name,
        ]);
    }

    public static function followGame(Request $request)
    {
        $user = auth()->user();
        //Obtener id del juego
        $id = $request['id'];

        //Si el usuario es el invitado verificar que no pasae de 5 seguidos
        if ($user->id == 1) {
            if ($user->followedGames()->count() >= 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes seguir mas de 5 juegos'
                ], 403);
            }
        }

        //Buscar el juego
        $game = Game::find($id);

        //Revisar si no esta el juego
        if (!$game) {
            //Buscar juego en la API RAWG
            $gameAPI = GameService::getGameAPI($id);

            //Si no se encontro el juego
            if (!$gameAPI) {
                return response()->json([
                    'message' => 'No se pudo encontrar el juego',
                    'success' => false
                ], 404);
            }

            //Almacenar en la base de datos
            $game = Game::create([
                'id' => $gameAPI['id'],
                'name' => $gameAPI['name'],
                'alternative_names' => count($gameAPI['alternative_names']) > 0 ? implode(" ", $gameAPI['alternative_names']) : null,
                'description' => $gameAPI['description'],
                'developers' => json_encode($gameAPI['developers']),
                'background_image' => $gameAPI['background_image'],
                'released' => $gameAPI['released'],
                'genres' => json_encode($gameAPI['genres']),
                'tags' => json_encode($gameAPI['tags']),
                'platforms' => json_encode($gameAPI['platforms'])
            ]);
        }

        //Verificar si ya lo esta siguiendo
        if ($user->followedGames()->where('game_id', $game->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ya sigues este juego'
            ], 400);
        }

        //Añadir relación
        $user->followedGames()->attach($game->id);

        return response()->json([
            'success' => true,
            'message' => 'Ahora sigues ' . $game->name
        ]);
    }

    public static function unfollowGame(Request $request)
    {
        $user = auth()->user();
        //Obtener id del juego
        $id = $request['id'];

        //Buscar el juego
        $game = Game::find($id);

        //Retornar respuesta si no se encuentra el juego
        if (!$game) {
            return response()->json([
                'success' => false,
                'message' => 'No se puedo encontrar el juego'
            ]);
        }

        //Verificar si lo esta siguiendo
        if (!$user->followedGames()->where('game_id', $game->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No sigues este juego'
            ], 400);
        }

        //Quitar relación
        $user->followedGames()->detach($game->id);

        return response()->json([
            'success' => true,
            'message' => 'Ya no sigues ' . $game->name
        ]);
    }

    public static function getFollowed(Request $request)
    {
        $user = User::find($request['id']);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontro el usuario'
            ], 404);
        }

        //Busar seguidos del usuario
        $usersFollowed = $user->following;
        $gamesFollowed = $user->followedGames;

        //Retornar respuesta
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name
            ],
            'games' => $gamesFollowed,
            'users' => $usersFollowed
        ]);
    }

    public static function getFollowers(Request $request)
    {
        $user = User::find($request['id']);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontro el usuario'
            ], 404);
        }

        //Buscar seguidores del usuario
        $usersFollowers = $user->followers;

        //Retornar respuesta
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name
            ],
            'users' => $usersFollowers
        ]);
    }
}
