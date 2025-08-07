<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('peppol_documents', function (Blueprint $table) {
            $table->id();

            // Informations de base
            $table->string('document_identifier');
            $table->enum('document_type', ['INVOICE', 'CREDIT_NOTE']);

            // Parties
            $table->json('sender')->nullable();
            $table->json('recipient')->nullable();

            // Adresses
            $table->json('sender_address')->nullable();
            $table->json('recipient_address')->nullable();

            // Dates
            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();

            // Données financières
            $table->string('currency')->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->decimal('subtotal', 10, 2)->nullable();
            $table->json('taxes')->nullable();
            $table->string('structured_communication', 20)->nullable();

            // Lignes de document
            $table->json('lines')->nullable();

            // Données brutes utiles pour debug
            $table->text('raw_xml')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peppol_documents');
    }
};
