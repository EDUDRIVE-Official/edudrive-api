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
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
        }

        Schema::create('academic_exams', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('course_id')->constrained('academic_courses')->cascadeOnDelete();
            $table->string('title', 180);
            $table->string('description', 2000)->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->integer('max_attempts')->default(1);
            $table->smallInteger('passing_score')->default(60);
            $table->boolean('shuffle_questions')->default(false);
            $table->string('feedback_mode', 20)->default('none');
            $table->timestampsTz();
        });

        Schema::create('academic_exam_questions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('exam_id')->constrained('academic_exams')->cascadeOnDelete();
            $table->foreignUuid('question_id')->constrained('academic_questions')->cascadeOnDelete();
            $table->integer('position');
            $table->integer('points')->default(1);
            $table->timestampsTz();
            $table->unique(['exam_id', 'position']);
            $table->unique(['exam_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_exam_questions');
        Schema::dropIfExists('academic_exams');
    }
};
