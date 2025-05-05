<?php

use Diji\Peppol\Http\Controllers\PeppolController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'api',
], function () {
    Route::post("/peppol/hook", [PeppolController::class, 'hook']);
    Route::middleware(['auth:api', "auth.tenant"])->group(function () {
        Route::post("/peppol/convert-to-ubl", [PeppolController::class, 'convertToUbl']);
    });
});
