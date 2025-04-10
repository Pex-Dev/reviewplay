<?php

namespace App\Http\Resources;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GamesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $datos = $request -> all();

        $url = 'https://api.rawg.io/api/games?key=a526235df343464e85174a0655b39f38';
        $solicitud = self::requestApi($url);

        $games = $solicitud['results'];
        $next = self::getNumberOfPage( $solicitud['next']) ?? null;
        $prev = self::getNumberOfPage( $solicitud['previous']) ?? null;

        return [
            'games' => $games,
            'next' => $next,
            'previous' => $prev
        ];
    }

    private static function getNumberOfPage($page){
        return str_replace('=','',explode('page',$page)[1]);
    }

    private static function requestApi($url, $method = 'GET'){
        //Crear cliente guzzle
        $client = new Client();
        try{
            //Realizar solicitud
            $response = $client->request($method, $url);
            //Decodificar JSON
            return json_decode($response->getBody()->getContents(), true);
        }catch(\Exception $e){
            return response()->json(['error' => $e], 500);
        }        
    }
}
