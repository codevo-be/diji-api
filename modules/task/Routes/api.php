<?php

use Diji\Task\Http\Controllers\ColumnController;
use Diji\Task\Http\Controllers\ItemController;
use Diji\Task\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'api',
], function () {
    Route::middleware(['auth:api', 'auth.tenant'])->group(function () {
        Route::put('/task-items/bulk-update', [ItemController::class, 'bulkUpdate']);
        Route::get('/task-projects/{project}/columns', [ColumnController::class, 'index']);
        Route::resource('/task-items', ItemController::class)->only(['store', 'update', 'destroy']);
        Route::resource('/task-columns', ColumnController::class)->only(['store', 'update']);
        Route::resource('/task-projects', ProjectController::class)->only(['index', 'store', 'show', 'update']);
    });
});
