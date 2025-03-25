<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->enum('status', \Diji\Billing\Models\RecurringInvoice::STATUSES)->default(\Diji\Billing\Models\RecurringInvoice::STATUS_DRAFT);
            $table->date('start_date')->nullable();
            $table->string('frequency')->nullable();
            $table->date('next_run_at')->nullable();
            $table->json('issuer')->nullable();
            $table->json('recipient')->nullable();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->decimal('subtotal', 10)->default(0)->nullable();
            $table->json('taxes')->nullable();
            $table->decimal('total', 10)->default(0)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_invoices');
    }
};
