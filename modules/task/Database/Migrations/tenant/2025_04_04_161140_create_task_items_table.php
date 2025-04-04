<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('task_column_id')
                ->constrained('task_columns');

            $table->string('name');
            $table->text('description')->nullable();

            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');

            $table->integer('priority')->default(1);
            $table->integer('order')->default(-1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_items');
    }
};





