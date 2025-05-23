<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix'     => 'api',
], function () {
    Route::middleware(["auth:api","auth.tenant"])->group(function(){
        /* Estimate */
        Route::resource("/estimates", Diji\Billing\Http\Controllers\EstimateController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::resource("/estimates/{estimate}/items", \Diji\Billing\Http\Controllers\BillingItemController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get("/estimates/{invoice}/pdf", [\Diji\Billing\Http\Controllers\EstimateController::class, "pdf"]);
        Route::post("/estimates/{invoice}/email", [\Diji\Billing\Http\Controllers\EstimateController::class, "email"]);

        /* Invoice */
        Route::delete("/invoices/batch", [\Diji\Billing\Http\Controllers\InvoiceController::class, "batchDestroy"]);
        Route::put("/invoices/batch", [\Diji\Billing\Http\Controllers\InvoiceController::class, "batchUpdate"]);
        Route::post('/invoices/batch/pdf', [\Diji\Billing\Http\Controllers\InvoiceController::class, "batchPdf"]);
        Route::resource("/invoices", Diji\Billing\Http\Controllers\InvoiceController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::resource("/invoices/{invoice}/items", \Diji\Billing\Http\Controllers\BillingItemController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get("/invoices/{invoice}/pdf", [\Diji\Billing\Http\Controllers\InvoiceController::class, "pdf"]);
        Route::post("/invoices/{invoice}/email", [\Diji\Billing\Http\Controllers\InvoiceController::class, "email"]);

        /* Recurring invoice */
        Route::resource("/recurring-invoices", Diji\Billing\Http\Controllers\RecurringInvoiceController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::resource("/recurring-invoices/{recurring_invoice}/items", \Diji\Billing\Http\Controllers\BillingItemController::class)->only(['index', 'store', 'update', 'destroy']);

        /* Credit note */
        Route::delete("/credit-notes/batch", [\Diji\Billing\Http\Controllers\CreditNoteController::class, "batchDestroy"]);
        Route::put("/credit-notes/batch", [\Diji\Billing\Http\Controllers\CreditNoteController::class, "batchUpdate"]);
        Route::post("/credit-notes/batch/pdf", [\Diji\Billing\Http\Controllers\CreditNoteController::class, "batchPdf"]);
        Route::resource("/credit-notes", Diji\Billing\Http\Controllers\CreditNoteController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::resource("/credit-notes/{credit_note}/items", \Diji\Billing\Http\Controllers\BillingItemController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get("/credit-notes/{credit_note}/pdf", [\Diji\Billing\Http\Controllers\CreditNoteController::class, "pdf"]);
        Route::post("/credit-notes/{credit_note}/email", [\Diji\Billing\Http\Controllers\CreditNoteController::class, "email"]);

        /* Self Invoice */
        Route::delete("/self-invoices/batch", [\Diji\Billing\Http\Controllers\SelfInvoiceController::class, "batchDestroy"]);
        Route::put("/self-invoices/batch", [\Diji\Billing\Http\Controllers\SelfInvoiceController::class, "batchUpdate"]);
        Route::post("/self-invoices/batch/pdf",  [\Diji\Billing\Http\Controllers\SelfInvoiceController::class, "batchPdf"]);
        Route::resource("/self-invoices", Diji\Billing\Http\Controllers\SelfInvoiceController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::resource("/self-invoices/{self_invoice}/items", \Diji\Billing\Http\Controllers\BillingItemController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::delete("/self-invoices/batch", [\Diji\Billing\Http\Controllers\SelfInvoiceController::class, "batchDestroy"]);
        Route::get("/self-invoices/{self_invoice}/pdf", [\Diji\Billing\Http\Controllers\SelfInvoiceController::class, "pdf"]);
        Route::post("/self-invoices/{self_invoice}/email", [\Diji\Billing\Http\Controllers\SelfInvoiceController::class, "email"]);

        /* Options */
        Route::resource("/transactions", \Diji\Billing\Http\Controllers\TransactionController::class)->only(["index", "show", 'update']);
        Route::get("/nordigen/institutions", [\Diji\Billing\Http\Controllers\NordigenController::class, 'institutions']);
    });
});

Route::get("/nordigen/callback", [\Diji\Billing\Http\Controllers\NordigenController::class, 'handleCallback']);
