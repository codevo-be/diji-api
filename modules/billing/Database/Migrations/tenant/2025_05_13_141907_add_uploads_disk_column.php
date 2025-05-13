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
        Schema::table('uploads', function (Blueprint $table) {
            $table->string('disk', 20)->default('public')->after('filename');
            $table->dropUnique('uploads_path_unique');
            $table->unique(['path', 'disk'], 'uploads_path_disk_unique');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('uploads', function (Blueprint $table) {
            $table->dropUnique('uploads_path_disk_unique');
            $table->dropColumn('disk');
            $table->unique('path', 'uploads_path_unique');
        });
    }
};
