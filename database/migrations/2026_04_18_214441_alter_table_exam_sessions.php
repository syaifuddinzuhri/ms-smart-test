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
        Schema::table('exam_sessions', function (Blueprint $table) {
            // Mencatat aktivitas terakhir (untuk deteksi heartbeat/koneksi putus)
            $table->dateTime('last_activity')->nullable()->after('remaining_duration');

            // Menyimpan riwayat pelanggaran dalam format JSON (Array of objects)
            $table->json('violation_log')->nullable()->after('last_violation_at');

            $table->index('status');
            $table->index('remaining_duration');
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->index('start_time');
            $table->index('end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn(['last_activity', 'violation_log']);
            $table->dropIndex('status');
            $table->dropIndex('remaining_duration');
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropIndex('start_time');
            $table->dropIndex('end_time');
        });
    }
};
