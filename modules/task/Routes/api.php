<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'api',
], function () {
    Route::middleware(['auth:api', 'auth.tenant'])->group(function () {
        Route::resource('/projects/{project}/task/groups', \Diji\Task\Http\Controllers\TaskGroupController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::resource('/projects/{project}/task/groups/{group}/items', \Diji\Task\Http\Controllers\TaskItemController::class)->only(['store', 'update', 'destroy']);
    });
});
