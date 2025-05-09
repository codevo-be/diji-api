<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_event_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('calendar_event_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            // Index pour améliorer les performances
            $table->index('calendar_event_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_event_user');
    }
};
