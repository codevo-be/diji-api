<?php

use Diji\Task\Http\Controllers\ColumnController;
use Diji\Task\Http\Controllers\ItemController;
use Diji\Task\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'api',
], function () {
    Route::middleware(['auth:api', 'auth.tenant'])->group(function () {
        Route::post('/task-items', [ItemController::class, 'store']);
        Route::post('/task-columns', [ColumnController::class, 'store']);
        Route::get('/task-projects/{project}/columns', [ColumnController::class, 'index']);
        Route::resource('/task-projects', ProjectController::class)->only(['index']);
    });
});
