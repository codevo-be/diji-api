<?php

use Diji\Peppol\Http\Controllers\PeppolController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix'     => 'api',
], function () {
    Route::middleware(['auth:api', "auth.tenant"])->group(function(){
    });
    Route::post("/peppol/convert-to-ubl", [PeppolController::class, 'convertToUbl']); // todo: pas oublier de le remettre au dessus
});
