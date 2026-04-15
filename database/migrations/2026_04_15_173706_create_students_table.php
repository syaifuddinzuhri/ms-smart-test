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
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Relasi
            $table->foreignUuid('user_id')->index()->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('classroom_id')->index()->constrained('classrooms')->cascadeOnDelete();

            // Profil Siswa
            $table->string('nisn')->unique()->index();
            $table->string('pob')->nullable(); // Place of Birth
            $table->date('dob')->nullable();    // Date of Birth

            $table->timestamps();
            $table->softDeletes()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
