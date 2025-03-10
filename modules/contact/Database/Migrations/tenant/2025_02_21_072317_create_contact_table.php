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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string("display_name");
            $table->string("firstname")->nullable();
            $table->string("lastname")->nullable();
            $table->string("email", 150)->unique()->nullable();
            $table->string("phone", 150)->nullable();
            $table->string("company_name")->nullable();
            $table->string("vat_number", 12)->nullable();

            $table->timestamps();
        });

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreignId('contact_id')->after("recipient")->nullable()->constrained('contacts')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};
