<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'api',
], function () {
    Route::middleware(['auth:api', "auth.tenant"])->group(function () {
        Route::resource("/histories", \Diji\History\Http\Controllers\HistoryController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    });
});
