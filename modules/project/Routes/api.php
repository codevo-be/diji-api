<?php

use Diji\Project\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'api',
], function () {
    Route::middleware(['auth:api', 'auth.tenant'])->group(function () {
        Route::resource('/projects', ProjectController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    });
});
