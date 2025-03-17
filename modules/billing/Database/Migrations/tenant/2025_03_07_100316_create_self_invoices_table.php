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
        Schema::create('self_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('identifier')->unique()->nullable();
            $table->unsignedInteger('identifier_number')->nullable();
            $table->enum('status', \Diji\Billing\Models\Invoice::STATUSES)->default(\Diji\Billing\Models\Invoice::STATUS_DRAFT);
            $table->json('issuer')->nullable();
            $table->json('recipient')->nullable();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->date("date");
            $table->date("due_date")->nullable();
            $table->date("payment_date")->nullable();
            $table->string("structured_communication", 12)->nullable();
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
        Schema::dropIfExists('self_invoices');
    }
};
