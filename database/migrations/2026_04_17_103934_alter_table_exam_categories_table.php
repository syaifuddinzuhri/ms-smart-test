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
        Schema::table('exam_categories', function (Blueprint $table) {
            $table->dropColumn('slug');
            $table->unique(['name', 'academic_year_id'], 'unique_name_per_academic_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_categories', function (Blueprint $table) {
            $table->dropUnique('unique_name_per_academic_year');
            $table->string('slug')->unique();
        });
    }
};
