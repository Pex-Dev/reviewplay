<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;



if (App::environment('production')) {
    //Necesario cuando el frontend y el backend estan en el mismo servidor
    Route::get('/{any}', function () {
        return File::get(public_path('index.html'));
    })->where('any', '.*');
} else {
    Route::get('/', function () {
        return ['Laravel' => app()->version()];
    });
}

require __DIR__ . '/auth.php';
