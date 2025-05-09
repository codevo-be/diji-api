<?php

use Illuminate\Support\Facades\Route;
use Diji\Calendar\Http\Controllers\CalendarEventController;

Route::group([
    'prefix' => 'api',
], function () {
    Route::middleware(['auth:api', 'auth.tenant'])->group(function () {
        Route::resource('/calendar', CalendarEventController::class)->only([
            'index', 'show', 'store', 'update', 'destroy'
        ]);
    });
});
