<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public static function show(Request $request)
    {
        $id = $request['id'];

        //Buscar ususario
        $user = User::find($id);

        //Retornar respuesta si no se encontro el usuario
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo encontrar al usuario'
            ], 404);
        }



        $response = [
            'success' => true,
            'user' => [
                'created_at' => $user->created_at,
                'description' => $user->description,
                'id' => $user->id,
                'image' => $user->image,
                'name' => $user->name,
                'verified' => false,
            ]
        ];


        //Obtener cantidad de reseñas hechas por el usuario 
        $reviewCount = $user->reviews()->count();
        $response['user']['reviewsCount'] = $reviewCount;

        //Obtener cantidad de favoritos del usuario
        $favoriteCount = $user->favoriteGames()->count();
        $response['user']['favoritesCount'] = $favoriteCount;

        //Obtener cantidad de seguidores del usuario
        $response['user']['followersCount'] = $user->followers()->count();

        //Obtener cantidad de usuarios y juegos seguidos
        $response['user']['followedCount'] = $user->followedGames()->count() +  $user->following()->count();

        //Ver si el usuario esta autenticado con sanctum
        $userAuth = Auth::guard('sanctum')->user();

        //Buscar los ultimos 4 juegos favoritos del usuario
        $latestFavorites = $user->favoriteGames()->latest()->take(4)->get();
        if (count($latestFavorites) > 0) {
            $response['user']['latestFavorites'] = $latestFavorites;
        }

        //Buscar ultimas 4 reseñas del usuario
        $latestReviews = $user->reviews()->with(['user', 'game'])->latest()->take(6)->get();
        if (count($latestReviews) > 0) {
            $response['user']['latestReviews'] = $latestReviews;
        }

        //Si el usuario (no el del perfil) esta autenticado
        if ($userAuth) {
            //Si el perfil es del usuario
            if ($userAuth->id == $id) {
                //Verificar si el usuario esta verificado
                if (!$userAuth->email_verified_at) {
                    return response()->json([
                        'success' => false,
                        'verified' => false,
                        'message' => 'Usuario no verificado'
                    ], 403);
                }
                $response['user']['verified'] = true;
            } else {
                //Ver si el usuario esta entre los seguidores del usuario al que queremos ver el perfil
                if ($user->followers()->where('follower_id', $userAuth->id)->exists()) {
                    $response['followed'] = true;
                } else {
                    $response['followed'] = false;
                }
            }
        }

        //Retornar respuesta 
        return response()->json($response);
    }

    public static function update(Request $request)
    {
        $profileId = $request['id'];

        //Verificar que el id sea el del usuario autenticado
        if ($profileId != auth()->user()->id) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'message' => 'El perfil no pertenece al usuario'
                ]
            ], 403);
        }

        //Para saber si el usuario subio una imagen nueva
        $nuevaImagen = false;

        //Validar description
        $validator = Validator::make($request->all(), [
            'description' => ['nullable', 'min:10', 'max:250']
        ]);

        //Retornar errores si fallo la validación
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }


        // Recibimos el Data URL de la imagen
        $dataUrl = $request->input('image');


        // Verificamos si hay una imagen
        if ($dataUrl) {
            // Extraemos el contenido base64 del Data URL
            $base64Image = preg_replace('/^data:image\/\w+;base64,/', '', $dataUrl);
            $imageData = base64_decode($base64Image);

            // Tipos permitidos
            $tiposPermitidos = [
                "image/jpeg",
                "image/png",
                "image/gif",
                "image/webp",
                "image/avif"
            ];

            // Obtener la extensión del archivo
            $extension = finfo_buffer(finfo_open(), $imageData, FILEINFO_MIME_TYPE);

            //Validar extensión del archivo
            if (!in_array($extension, $tiposPermitidos)) {
                return response()->json([
                    'success' => false,
                    'errors' => [
                        'message' => 'El formato del archivo no es valido'
                    ]
                ], 415);
            }

            // Obtenemos el tamaño de la imagen en bytes
            $imageSize = strlen($imageData);

            //Tamaño maximo del archivo (5MB)
            $maxSize = 5 * 1024 * 1024;

            //Validar que la imagen no sobrepase el tamaño maximo
            if ($imageSize > $maxSize) {
                return response()->json([
                    'success' => false,
                    'errors' => [
                        'message' => 'El tamaño de la imagen no puede ser superior a 5MB'
                    ]
                ], 413);
            }


            // Generamos un nombre único para el archivo
            $fileName = uniqid() . '.png';

            // Ruta de destino absoluta
            $path = public_path('uploads/' . $fileName);

            // Intentamos guardar la imagen directamente
            $escrito = file_put_contents($path, $imageData);

            if (!$escrito) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al subir la imagen'
                ], 500);
            }

            $nuevaImagen = true;
        }

        //Obtener usuario
        $user = auth()->user();

        //Verificar si se debe agregar una imagen al usuario
        if ($nuevaImagen) {
            //Verificar si el usuario ya tiene una imagen
            if ($user->image) {
                //Eliminar imagen anterior
                Storage::disk('public')->delete($user->image);
            }
            //Asignar nueva imagen al usuario
            $user->image = $fileName;
        }

        //Verificar si se paso una descripción
        if ($request['description']) {
            $user->description = $request['description'];
        }

        //Guardar cambios al usuario
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado correctamente'
        ]);
    }

    //Cambiar contraseña
    public static function updatePassword(Request $request)
    {
        $profileId = $request['id'];

        //Obtener al usuario
        $user = auth()->user();

        //Verificar que el id sea el del usuario autenticado
        if ($profileId != $user->id) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'message' => 'El perfil no pertenece al usuario'
                ]
            ], 403);
        }

        //Si el id del usuario es 1 significa que es invitado por lo que no puede cambiar contraseña
        if ($user->id == 1) {
            return response()->json([
                'success' => false,
                'errors' => [
                    'message' => 'El usuario invitado no puede cambiar la contraseña'
                ]
            ], 403);
        }

        //Validar 
        $validator = Validator::make($request->all(), [
            'current_password' => ['required'],
            'new_password' => ['required', 'string', 'min:8', 'max:15', 'confirmed'],
        ]);

        //Verificar si hay errores
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        //Verificar si la contraseña actual es correcta
        if (!Hash::check($request['current_password'], $user->password)) {
            return response()->json(
                [
                    'success' => false,
                    'errors' => [
                        'current_password' => ['La contraseña actual es incorrecta']
                    ]
                ],
                422
            );
        }

        //Guardar nueva contraseña
        $user->password = Hash::make($request['new_password']);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente'
        ]);
    }
}
