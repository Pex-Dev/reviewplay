<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Review;
use GuzzleHttp\Client;

class GameService
{
    public static function getGameAPI($id)
    {
        $apiKey = env('API_RAWGD');
        $url = 'https://api.rawg.io/api/games/' . $id . '?key=' . $apiKey;
        $response = self::requestApi($url);

        if (isset($response['error']) && $response['error'] === true) {
            return null;
        }

        $game = [
            'id' => $response['id'],
            'name' => $response['name'],
            'alternative_names' => $response['alternative_names'],
            'description' => $response['description'],
            'developers' => $response['developers'],
            'background_image' => $response['background_image'],
            'released' => $response['released'],
            'genres' => $response['genres'],
            'tags' => $response['tags'],
            'platforms' => $response['platforms'],
            'latestReviews' => [],
            'raw' => $response
        ];


        return $game;
    }

    public static function getGameDB($id)
    {
        $gameDB = Game::find($id);

        if (!$gameDB) {
            return null;
        }

        //Buscar las reviews del juego
        $reviews = $gameDB->reviews()->with('user')->latest()->take(4)->get();

        //Buscar score promedio del juego
        $averageScore = Review::where('game_id', $id)->avg('score');



        $game = [
            'id' => $gameDB['id'],
            'name' => $gameDB['name'],
            'alternative_names' => $gameDB['alternative_names'],
            'description' => $gameDB['description'],
            'developers' => json_decode($gameDB['developers']),
            'background_image' => $gameDB['background_image'],
            'released' => $gameDB['released'],
            'genres' => json_decode($gameDB['genres']),
            'tags' => json_decode($gameDB['tags']),
            'platforms' => json_decode($gameDB['platforms'])
        ];


        if ($reviews) {
            $game['latestReviews'] = $reviews;
        }

        if ($averageScore) {
            $game['averageScore'] = floatval($averageScore);
        }

        return $game;
    }

    public static function searchGamesDB($name)
    {
        $games = Game::where('name', 'LIKE', "%$name%")
            ->orWhere('alternative_names', 'LIKE', "%$name%")
            ->get();

        return [
            'results' => $games
        ];
    }

    public static function requestApi($url, $method = 'GET')
    {
        // Crear cliente Guzzle
        $client = new Client();
        try {
            // Realizar solicitud
            $response = $client->request($method, $url);

            // Verificar el código de estado HTTP
            if ($response->getStatusCode() !== 200) {
                return [
                    'error' => true,
                    'message' => 'Error: Código de estado HTTP ' . $response->getStatusCode(),
                ];
            }

            // Decodificar JSON
            $data = json_decode($response->getBody()->getContents(), true);

            // Verificar si la decodificación fue exitosa
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'error' => true,
                    'message' => 'Error al decodificar JSON: ' . json_last_error_msg(),
                ];
            }

            // Si todo es correcto, devolver los datos
            return $data;
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => 'Error en la solicitud: ' . $e->getMessage(),
            ];
        }
    }
}
