<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_columns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('order')->default(0);
            $table->unsignedBigInteger('columnable_id');
            $table->string('columnable_type');
            $table->timestamps();

            $table->index(['columnable_id', 'columnable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_columns');
    }
};



