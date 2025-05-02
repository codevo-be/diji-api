<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/* Upload */
//Route::get('/{tenant}/uploads/{year}/{month}/{filename}', [\App\Http\Controllers\UploadController::class, 'show']);
