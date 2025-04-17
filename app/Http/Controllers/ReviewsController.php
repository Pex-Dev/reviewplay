<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Review;
use App\Models\User;
use App\Notifications\NewReviewNotification;
use App\Services\GameService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReviewsController extends Controller
{
    public static function index(Request $request)
    {
        //Buscar al usuario usando el token por medio de sanctum
        $user = Auth::guard('sanctum')->user();

        $query = Review::with(['game', 'user']);

        //Si el usuario esta autenticado 
        if ($user) {
            if ($user->id != 1) {
                //Excluir los registro del usuario con id 1 ya que es el invitado y como no requiere autenticación los usuarios pueden subir cosas inadecuadas
                $query = $query->where('user_id', '!=', 1);
            }
        } else {
            $query = $query->where('user_id', '!=', 1);
        }



        // Aplicar orden según el parámetro 'order' en la URL
        if ($request->has('order')) {
            if ($request->order === 'newest') {
                $query->orderBy('created_at', 'desc'); // Más recientes primero
            } elseif ($request->order === 'oldest') {
                $query->orderBy('created_at', 'asc'); // Más antiguos primero
            } elseif ($request->order === 'highest') {
                $query->orderBy('score', 'desc'); // Mejor valorado
            } elseif ($request->order === 'lowest') {
                $query->orderBy('score', 'asc'); // Pero valorado
            } else {
                $query->orderBy('created_at', 'desc');
            }
        } else {
            // Orden por defecto
            $query->orderBy('created_at', 'desc');
        }

        $reviews = $query->paginate(21);
        //Enviar respuesta
        return response()->json([
            'success' => true,
            'reviews' => $reviews
        ]);
    }

    public static function show(Request $request)
    {
        $id = $request['id'];

        //Buscar review
        $review = Review::with(['user', 'game'])->find($id);

        if (!$review) {
            return response()->json([
                'success' => false,
                'errors' => ['message' => 'No se encontro la reseña'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'review' => $review
        ]);
    }

    public static function store(Request $request)
    {
        //Validar formulario
        $validator = Validator::make($request->all(), [
            'score' => ['required', 'numeric', 'between:1,10'],
            'review' => ['required', 'min:10', 'max:700']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'succes' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $apiId = $request['id'];

        //Ver si el juego ya tiene una reseña del usuario
        $reviewedGame = auth()->user()->reviews()->where('game_id', $apiId)->exists();

        //Si esta en favoritos retornar
        if ($reviewedGame) {
            return response()->json([
                'errors' => ['message' => 'Ya hay una reseña para este juego'],
                'success' => false
            ], 409);
        }

        //Si el usuario es invitado verificar que no supere la 5 reseñas como ma
        if (auth()->user()->id == 1) {
            if (auth()->user()->reviews()->count() >= 5) {
                return response()->json([
                    'errors' => ['message' => 'No puedes tener más de 5 reseñas'],
                    'success' => false
                ], 409);
            }
        }


        //Buscar juego en la base de datos
        $game = Game::find($apiId);

        //Si el juego no esta en la base de datos se busca con la API
        if (!$game) {
            //Buscar juego en la API RAWG
            $gameAPI = GameService::getGameAPI($apiId);

            //Si no se encontro el juego
            if (!$gameAPI) {
                return response()->json([
                    'errors' => ['message' => 'No se pudo encontrar el juego'],
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
        };

        //Crear reseña
        $review = Review::create([
            'game_id' => $game->id,
            'user_id' => auth()->user()->id,
            'score' => $request['score'],
            'review' => $request['review']
        ]);

        //Añadir relación con el usuario
        $review->load('user');


        //Obtener seguidores del juego y seguidores del usuario
        $userFollowers = auth()->user()->followers;
        $gameFollowers = $game->followers;

        // Combinar y eliminar duplicados por ID
        $notifiedUsers = $userFollowers->merge($gameFollowers)->unique('id');

        // Enviar notificación una sola vez a cada usuario
        foreach ($notifiedUsers as $follower) {
            $follower->notify(new NewReviewNotification(auth()->user(), $review));
        }

        //Enviar respuesta 
        return response()->json([
            'success' => true,
            'message' => 'Reseña añadida correctamente',
            'review' => $review
        ]);
    }

    public static function update(Request $request)
    {
        $id = $request['id'];

        //Validar formulario
        $validator = Validator::make($request->all(), [
            'score' => ['required', 'numeric', 'between:1,10'],
            'review' => ['required', 'min:10', 'max:700']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'succes' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        //Buscar review
        $review = auth()->user()->reviews()->where('id', $id);

        if (!$review) {
            return response()->json([
                'success' => false,
                'errors' => ['message' => 'No se encontro la reseña o no pertence al usuario'],
            ], 404);
        }

        $review->update([
            'score' => $request['score'],
            'review' => $request['review']
        ]);


        return response()->json([
            'success' => true,
            'message' => 'Reseña actualizada correctamente'
        ]);
    }
    public static function destroy(Request $request)
    {
        $id = $request['id'];

        //Buscar review
        $review = auth()->user()->reviews()->where('id', $id);

        if (!$review) {
            return response()->json([
                'success' => false,
                'errors' => ['message' => 'No se encontro la reseña o no pertence al usuario'],
            ], 404);
        }

        $review->delete();


        return response()->json([
            'success' => true,
            'message' => 'Reseña eliminada correctamente'
        ]);
    }

    public function getGameReviews(Request $request)
    {
        $id = $request['id'];

        //Buscar juego
        $game = Game::find($id);

        //Retornar respuesta si no se encontro
        if (!$game) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo encontrar el juego'
            ], 404);
        }

        $query = $game->reviews()->with(['game', 'user']);

        //Revisar los filtros enviados desde frontend
        if ($request->has('order')) {
            //Si order fue newest
            if ($request['order'] === 'newest') {
                $query->latest();
            }
            //Si order fue oldest
            if ($request['order'] === 'oldest') {
                $query->oldest();
            }
            //Si order fue highest
            if ($request['order'] === 'highest') {
                $query->orderBy('score', 'desc');
            }
            //Si order fue lowest
            if ($request['order'] === 'lowest') {
                $query->orderBy('score', 'asc');
            }
        }

        //Obtener reviews paginados
        $reviews = $query->paginate(20);

        return response()->json([
            'success' => true,
            'game' => $game,
            'reviews' => $reviews
        ]);
    }

    public static function getUserReviews(Request $request)
    {
        $id = $request['id'];

        //Obtener el usuario
        $user = User::find($id);

        //Retornar respuesta si no esta el usuario
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario no existe'
            ], 404);
        }

        //Obtener todas las reseñas del usuario
        $query = $user->reviews()->with(['game', 'user']);

        // Aplicar orden según el parámetro 'order' en la URL
        if ($request->has('order')) {
            if ($request->order === 'newest') {
                $query->orderBy('created_at', 'desc'); // Más recientes primero
            } elseif ($request->order === 'oldest') {
                $query->orderBy('created_at', 'asc'); // Más antiguos primero
            } elseif ($request->order === 'highest') {
                $query->orderBy('score', 'desc'); // Más recientes primero
            } elseif ($request->order === 'lowest') {
                $query->orderBy('score', 'asc'); // Más recientes primero
            } else {
                $query->orderBy('created_at', 'desc');
            }
        } else {
            // Orden por defecto
            $query->orderBy('created_at', 'desc');
        }

        $reviews = $query->paginate(20);


        return response()->json([
            'success' => true,
            'reviews' => $reviews,
            'user' => [
                'id' => $user->id,
                'name' => $user->name
            ]
        ]);
    }
}
