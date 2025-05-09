<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MetaController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);


Route::middleware(['auth:api', 'auth.tenant'])->prefix('auth')->group(function () {
    Route::get('/user', [AuthController::class, 'getAuthenticatedUser']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/users', [AuthController::class, 'getUsersForTenant']);
});

Route::middleware(['auth:api', 'auth.tenant'])->group(function () {
    Route::resource('/metas', MetaController::class)->only(["show", "update"]);
    Route::resource('/uploads', UploadController::class)->only('store');
});
