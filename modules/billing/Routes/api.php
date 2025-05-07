<?php

use Diji\Billing\Http\Controllers\BillingItemController;
use Diji\Billing\Http\Controllers\CreditNoteController;
use Diji\Billing\Http\Controllers\InvoiceController;
use Diji\Billing\Http\Controllers\NordigenController;
use Diji\Billing\Http\Controllers\SelfInvoiceController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'api',
], function () {
    Route::middleware(["auth:api", "auth.tenant"])->group(function () {
        /* Invoice */
        Route::delete("/invoices/batch", [InvoiceController::class, "batchDestroy"]);
        Route::put("/invoices/batch", [InvoiceController::class, "batchUpdate"]);
        Route::post('/invoices/batch/pdf', [InvoiceController::class, "batchPdf"]);
        Route::resource("/invoices", Diji\Billing\Http\Controllers\InvoiceController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::resource("/invoices/{invoice}/items", BillingItemController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get("/invoices/{invoice}/pdf", [InvoiceController::class, "pdf"]);
        Route::post("/invoices/{invoice}/email", [InvoiceController::class, "email"]);
        Route::post("/invoices/{invoice}/send-to-peppol", [InvoiceController::class, "sendToPeppol"]);

        /* Recurring invoice */
        Route::resource("/recurring-invoices", Diji\Billing\Http\Controllers\RecurringInvoiceController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::resource("/recurring-invoices/{recurring_invoice}/items", BillingItemController::class)->only(['index', 'store', 'update', 'destroy']);

        /* Credit note */
        Route::delete("/credit-notes/batch", [CreditNoteController::class, "batchDestroy"]);
        Route::put("/credit-notes/batch", [CreditNoteController::class, "batchUpdate"]);
        Route::resource("/credit-notes", Diji\Billing\Http\Controllers\CreditNoteController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::resource("/credit-notes/{credit_note}/items", BillingItemController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get("/credit-notes/{credit_note}/pdf", [CreditNoteController::class, "pdf"]);
        Route::post("/credit-notes/{credit_note}/email", [CreditNoteController::class, "email"]);
        Route::post("/credit-notes/{credit_note}/send-to-peppol", [CreditNoteController::class, "sendToPeppol"]);

        /* Self Invoice */
        Route::delete("/self-invoices/batch", [SelfInvoiceController::class, "batchDestroy"]);
        Route::put("/self-invoices/batch", [SelfInvoiceController::class, "batchUpdate"]);
        Route::resource("/self-invoices", Diji\Billing\Http\Controllers\SelfInvoiceController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::resource("/self-invoices/{self_invoice}/items", BillingItemController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::delete("/self-invoices/batch", [SelfInvoiceController::class, "batchDestroy"]);
        Route::get("/self-invoices/{self_invoice}/pdf", [SelfInvoiceController::class, "pdf"]);
        Route::post("/self-invoices/{self_invoice}/email", [SelfInvoiceController::class, "email"]);

        /* Options */
        Route::get("/nordigen/institutions", [NordigenController::class, 'institutions']);
    });
});

Route::get("/nordigen/callback", [NordigenController::class, 'handleCallback']);
