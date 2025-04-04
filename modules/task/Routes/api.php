<?php

use Diji\Task\Http\Controllers\ColumnController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix'     => 'api',
], function () {
    Route::middleware(['auth:api', "auth.tenant"])->group(function(){
        Route::resource("/task-columns", ColumnController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    });
});

