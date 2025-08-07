<?php

use Diji\Expense\Http\Controllers\ExpenseController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'api',
], function () {
    Route::middleware(['auth:api', "auth.tenant"])->group(function () {
        Route::resource("/expense", ExpenseController::class)->only(['index', 'show']);
    });
});
