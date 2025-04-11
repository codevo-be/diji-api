<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'api',
], function () {
    Route::middleware(['auth:api', "auth.tenant"])->group(function(){
        Route::resource("/teams", \Diji\Team\Http\Controllers\TeamController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    });
});
