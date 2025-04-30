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
        Schema::create('invoice_email_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('invoice_id');
            $table->string('recipient_email');
            $table->timestamp('sent_at')->nullable();
            $table->integer('extended_date');
            $table->boolean('success')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_email_logs');
    }
};
