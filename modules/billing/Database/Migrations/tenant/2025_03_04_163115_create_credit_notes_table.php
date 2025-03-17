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
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('identifier')->unique()->nullable();
            $table->unsignedInteger('identifier_number')->nullable();
            $table->enum('status', \Diji\Billing\Models\CreditNote::STATUSES)->default(\Diji\Billing\Models\CreditNote::STATUS_DRAFT);
            $table->json('issuer')->nullable();
            $table->json('recipient')->nullable();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->date("date");
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
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
        Schema::dropIfExists('credit_notes');
    }
};
