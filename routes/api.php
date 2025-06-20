<?php


use App\Http\Controllers\FileController;
use App\Http\Controllers\MetaController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [\App\Http\Controllers\AuthController::class, 'login']);
Route::post("/auth/forgot-password", [\App\Http\Controllers\AuthController::class, 'forgotPassword']);
Route::post("/auth/reset-password", [\App\Http\Controllers\AuthController::class, 'resetPassword']);


Route::middleware(['auth:api', 'auth.tenant'])->prefix('auth')->group(function () {
    Route::get('/user', [\App\Http\Controllers\AuthController::class, 'getAuthenticatedUser']);
    Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout']);
});

Route::middleware(['auth:api', 'auth.tenant'])->group(function () {
    Route::resource('/metas', MetaController::class)->only(["show", "update"]);
    Route::resource('/uploads', UploadController::class)->only('store');
    Route::post('/csv', [FileController::class, 'csv']);
});
