<?php

use Diji\Task\Http\Controllers\TaskGroupController;
use Diji\Task\Http\Controllers\TaskItemController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'api',
], function () {
    Route::middleware(['auth:api', 'auth.tenant'])->group(function () {
        Route::resource('/projects/{project}/task/groups', TaskGroupController::class)
            ->only(['index', 'show', 'store', 'update', 'destroy']);

        Route::resource('/projects/{project}/task/groups/{group}/items', TaskItemController::class)
            ->only(['store', 'update', 'destroy']);

        Route::put('/projects/{project}/task/items/bulk', [TaskItemController::class, 'bulkUpdate']);
    });
});
