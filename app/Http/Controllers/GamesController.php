<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Services\GameService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use function PHPUnit\Framework\isNumeric;


class GamesController extends Controller
{
    public function index(Request $request)
    {
        //Obtener datos enviados en la solicitud
        $page = $request->get('page', 1);
        $genres = $request->get('genres', null);
        $tags = $request->get('tags', null);
        $platforms = $request->get('platforms', null);
        $order = $request->get('order', null);
        $searchValue = $request->get('search', null);

        //Obtener api key
        $apiKey = env('API_RAWGD');

        //Se usara para buscar en la base de datos si se requiere
        $searchInDB = false;

        $url = 'https://api.rawg.io/api/games?key=' . $apiKey;


        if ($genres) {
            $url .= '&genres=' . $genres;
        }
        if ($tags) {
            $url .= '&tags=' . $tags;
        }
        if ($platforms) {
            $url .= '&platforms=' . $platforms;
        }
        if ($searchValue) {
            $url .= '&search=' . $searchValue;
        }

        if ($order) {
            switch ($order) {
                case 'newest':
                    $url .= "&ordering=-released";
                    break;
                case 'oldest':
                    $url .= "&ordering=released";
                    break;
                case 'highest': //Se buscarán los resultados en la base de datos
                    $searchInDB = true;
                    break;
                case 'lowest': //Se buscarán los resultados en la base de datos
                    $searchInDB = true;
                    break;

                default:
                    break;
            }
        }

        if ($page) {
            if (isNumeric($page)) {
                $page = (int)$page;
            } else {
                $page = 1;
            }
            $url .= "&page=" . $page;
        }

        //Si se requiere buscar en base de datos
        if ($searchInDB) {
            $query = Game::withAvg('reviews', 'score'); //Obtener juegos con el score promedio

            if ($searchValue) {
                //buscar por nombre
                $query->where('name', 'LIKE', "%{$searchValue}%")
                    ->orWhere('alternative_names', 'LIKE', "%$searchValue%");
            }

            if ($order == "highest") { //Ordenar por reseñas altas
                $query->orderByDesc('reviews_avg_score');
            }
            if ($order == "lowest") { //Ordenar por reseñas bajas
                $query->orderByRaw('reviews_avg_score IS NULL, reviews_avg_score ASC');
            }
            $games = $query->paginate(20);

            return response()->json([
                'games' => $games->items(),
                'raw' => $games,
                'next' => $this->getNumberOfPage($games->nextPageUrl() ?? null),
                'previous' => $this->getNumberOfPage($games->previousPageUrl() ?? null),
                'page' => $games->currentPage()
            ]);
        }

        $solicitud = GameService::requestApi($url);

        $respuesta = [
            'games' => $solicitud['results'] ?? [],
            'raw' => $solicitud,
            'next' => $this->getNumberOfPage($solicitud['next'] ?? null),
            'previous' => $this->getNumberOfPage($solicitud['previous'] ?? null),
            'page' => $page
        ];
        return response()->json($respuesta);
    }

    public static function show(Request $request)
    {
        //Obtener datos enviados en la solicitud
        $id = $request['id'];

        //Buscar ese juego en la base de datos
        $game = GameService::getGameDB($id);

        //Si no esta en la base de datos obtenerlo desde la API
        if (!$game) {
            $game = GameService::getGameAPI($id);
        }

        //Si no se encontro el juego
        if (!$game) {
            return response()->json([
                'message' => 'No se pudo encontrar el juego'
            ], 404);
        }

        //Respuesta inicial
        $respuesta = [
            'game' => $game ?? [],
        ];



        //Buscar al usuario usando el token por medio de sanctum
        $user = Auth::guard('sanctum')->user();

        //Si el usuario esta autenticado se envia si el juego ya esta en favoritos o no
        if ($user) {
            //Buscar el juego en los favoritos del usuario
            $favoriteGame = $user->favoriteGames()->where('games.id', $id)->exists();
            $respuesta['inFavorites'] = $favoriteGame;

            //Buscar reseña de usuario
            $review = $user->reviews()->with('user')->where('game_id', $id)->first();
            if ($review) {
                $respuesta['userReview'] = $review;
            }

            //Buscar si el usuario sigue el juego
            $respuesta['game']['followed'] = $user->followedGames()->where('game_id', $game['id'])->exists();
        }

        return response()->json($respuesta);
    }

    private function getNumberOfPage($page)
    {
        if (!$page) {
            return null;
        }

        preg_match('/page=(\d+)/', $page, $matches);
        if (count($matches) == 0) {
            return 1;
        }
        return (int)$matches[1] ?? null;
    }
}
