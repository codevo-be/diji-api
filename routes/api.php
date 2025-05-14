<?php

use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [\App\Http\Controllers\AuthController::class, 'login']);
Route::post("/auth/forgot-password", [\App\Http\Controllers\AuthController::class, 'forgotPassword']);
Route::post("/auth/reset-password", [\App\Http\Controllers\AuthController::class, 'resetPassword']);


Route::middleware(['auth:api','auth.tenant'])->prefix('auth')->group(function () {
    Route::get('/user', [\App\Http\Controllers\AuthController::class, 'getAuthenticatedUser']);
    Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout']);
});

Route::middleware(['auth:api','auth.tenant'])->group(function () {
    Route::resource('/metas', \App\Http\Controllers\MetaController::class)->only(["show","update"]);
    Route::resource('/uploads', \App\Http\Controllers\UploadController::class)->only('store');
    Route::post('/tenants/register', [\App\Http\Controllers\TenantController::class, 'register']);
});
