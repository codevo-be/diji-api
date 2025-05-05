<?php

use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [\App\Http\Controllers\AuthController::class, 'login']);


Route::middleware(['auth:api','auth.tenant'])->prefix('auth')->group(function () {
    Route::get('/user', [\App\Http\Controllers\AuthController::class, 'getAuthenticatedUser']);
    Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout']);
});

Route::middleware(['auth:api','auth.tenant'])->group(function () {
    Route::resource('/metas', \App\Http\Controllers\MetaController::class)->only(["show","update"]);
    Route::resource('/uploads', \App\Http\Controllers\UploadController::class)->only('store');

    Route::put('/tenant', [\App\Http\Controllers\TenantController::class, 'update']);
});
