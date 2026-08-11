<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_exam_attempts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('exam_id')->constrained('academic_exams')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20);
            $table->dateTimeTz('started_at');
            $table->dateTimeTz('submitted_at')->nullable();
            $table->string('title', 180);
            $table->integer('duration_minutes')->nullable();
            $table->smallInteger('passing_score');
            $table->boolean('shuffle_questions');
            $table->string('feedback_mode', 20);
            $table->integer('score')->default(0);
            $table->integer('total_points')->default(0);
            $table->integer('percentage')->default(0);
            $table->boolean('passed')->default(false);
            $table->timestampsTz();
        });

        Schema::create('academic_exam_attempt_questions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('attempt_id')->constrained('academic_exam_attempts')->cascadeOnDelete();
            $table->integer('position');
            $table->foreignUuid('question_id')->constrained('academic_questions');
            $table->integer('points');
            $table->text('prompt');
            $table->string('type', 20);
            $table->jsonb('options')->nullable();
            $table->jsonb('correct_response');
            $table->text('explanation')->nullable();
            $table->jsonb('user_response')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->dateTimeTz('answered_at')->nullable();
            $table->timestampsTz();
            $table->unique(['attempt_id', 'position']);
            $table->unique(['attempt_id', 'question_id']);
        });

        $sql = 'CREATE UNIQUE INDEX academic_exam_attempts_active_unique '
            .'ON academic_exam_attempts (exam_id, user_id) '
            ."WHERE status = 'in_progress'";
        DB::statement($sql);
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_exam_attempt_questions');
        Schema::dropIfExists('academic_exam_attempts');
    }
};
