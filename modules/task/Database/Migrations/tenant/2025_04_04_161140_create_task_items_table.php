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
            $table->foreignId('task_group_id')->constrained('task_groups');
            $table->integer('task_number');
            $table->string('name');
            $table->text('description')->nullable();

            $table->enum('status', \Diji\Task\Models\TaskItem::STATUSES)->default('pending');

            $table->integer('priority')->default(1);
            $table->integer('position')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_items');
    }
};





