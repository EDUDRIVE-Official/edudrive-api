<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DURATION_MINUTES_DEFINITION =
        'integer check (duration_minutes is null or duration_minutes > 0)';

    public function up(): void
    {
        Schema::create('academic_unit_contents', function (Blueprint $table): void {
            $table->foreignUuid('unit_id')->primary()->constrained('academic_course_units')->cascadeOnDelete();
            $table->timestampsTz();
        });

        Schema::create('academic_lessons', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('unit_id')->constrained('academic_unit_contents', 'unit_id')->cascadeOnDelete();
            $table->string('code', 60);
            $table->string('title', 180);
            $table->text('summary')->nullable();
            $table->rawColumn('duration_minutes', self::DURATION_MINUTES_DEFINITION)->nullable();
            $table->integer('position');
            $table->timestampsTz();
            $table->unique(['unit_id', 'code']);
            $table->unique(['unit_id', 'position']);
        });

        Schema::create('academic_lesson_blocks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('lesson_id')->constrained('academic_lessons')->cascadeOnDelete();
            $table->string('type', 30);
            $table->integer('position');
            $table->json('payload');
            $table->timestampsTz();
            $table->unique(['lesson_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_lesson_blocks');
        Schema::dropIfExists('academic_lessons');
        Schema::dropIfExists('academic_unit_contents');
    }
};
