<?php

use Diji\Contact\Http\Controllers\ContactController;
use Diji\Contact\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'api',
], function () {
    Route::middleware(['auth:api', "auth.tenant"])->group(function () {
        Route::resource("/contacts", ContactController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::post("/contacts/import", [ImportController::class, 'index']);
    });
});
