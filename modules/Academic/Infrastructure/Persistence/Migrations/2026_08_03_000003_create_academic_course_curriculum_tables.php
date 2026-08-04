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
        Schema::create('academic_course_modules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('course_id')->constrained('academic_courses')->cascadeOnDelete();
            $table->string('code', 60);
            $table->string('title', 180);
            $table->text('description');
            $table->text('objectives')->nullable();
            $table->rawColumn(
                'duration_minutes',
                self::DURATION_MINUTES_DEFINITION,
            )->nullable();
            $table->integer('position');
            $table->timestampsTz();
            $table->unique(['course_id', 'code']);
            $table->unique(['course_id', 'position']);
        });

        Schema::create('academic_course_units', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('module_id')->constrained('academic_course_modules')->cascadeOnDelete();
            $table->string('code', 60);
            $table->string('title', 180);
            $table->text('description');
            $table->text('objectives')->nullable();
            $table->rawColumn(
                'duration_minutes',
                self::DURATION_MINUTES_DEFINITION,
            )->nullable();
            $table->integer('position');
            $table->timestampsTz();
            $table->unique(['module_id', 'code']);
            $table->unique(['module_id', 'position']);
        });

        Schema::create('academic_module_prerequisites', function (Blueprint $table): void {
            $table->foreignUuid('module_id')->constrained('academic_course_modules')->cascadeOnDelete();
            $table->foreignUuid('prerequisite_module_id')->constrained('academic_course_modules')->cascadeOnDelete();
            $table->primary(['module_id', 'prerequisite_module_id']);
        });

        Schema::create('academic_unit_prerequisites', function (Blueprint $table): void {
            $table->foreignUuid('unit_id')->constrained('academic_course_units')->cascadeOnDelete();
            $table->foreignUuid('prerequisite_unit_id')->constrained('academic_course_units')->cascadeOnDelete();
            $table->primary(['unit_id', 'prerequisite_unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_unit_prerequisites');
        Schema::dropIfExists('academic_module_prerequisites');
        Schema::dropIfExists('academic_course_units');
        Schema::dropIfExists('academic_course_modules');
    }
};
