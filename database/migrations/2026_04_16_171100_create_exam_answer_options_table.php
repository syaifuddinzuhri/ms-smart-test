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
        Schema::create('exam_answer_options', function (Blueprint $table) {
            $table->foreignUuid('exam_answer_id')->constrained('exam_answers')->cascadeOnDelete();
            $table->foreignUuid('question_option_id')->constrained('question_options')->cascadeOnDelete();
            $table->primary(['exam_answer_id', 'question_option_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_answer_options');
    }
};
