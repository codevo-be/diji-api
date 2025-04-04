<?php

use Diji\Task\Http\Controllers\ColumnController;
use Diji\Task\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'api',
], function () {
    Route::middleware(['auth:api', 'auth.tenant'])->group(function () {
        Route::resource('/task-columns', ColumnController::class)->only(['index']);
        Route::resource('/task-projects', ProjectController::class)->only(['index']);
    });
});
