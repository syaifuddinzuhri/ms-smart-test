<?php

use App\Enums\ExamStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();

            $table->unsignedInteger('question_seed')->nullable();
            $table->unsignedInteger('option_seed')->nullable();

            $table->enum('status', ExamStatus::values())->default(ExamStatus::PENDING->value);
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->integer('remaining_duration')->nullable(); // Dalam detik

            // Monitoring Pelanggaran
            $table->integer('violation_count')->default(0);
            $table->dateTime('last_violation_at')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable(); // Menyimpan info browser & device
            $table->string('device_type')->nullable(); // Optional: mobile, tablet, desktop
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_sessions');
    }
};
