<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_programs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 60)->unique();
            $table->string('title', 180);
            $table->text('description');
            $table->unsignedSmallInteger('min_age')->nullable();
            $table->unsignedSmallInteger('max_age')->nullable();
            $table->string('status', 30)->index();
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('academic_program_courses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('program_id')->constrained('academic_programs')->cascadeOnDelete();
            $table->foreignUuid('course_id')->constrained('academic_courses')->restrictOnDelete();
            $table->unsignedInteger('position');
            $table->timestampsTz();
            $table->unique(['program_id', 'course_id']);
            $table->unique(['program_id', 'position']);
        });

        Schema::create('academic_program_license_stages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('program_id')->constrained('academic_programs')->cascadeOnDelete();
            $table->string('value', 30);
            $table->unsignedInteger('position');
            $table->timestampsTz();
            $table->unique(['program_id', 'value']);
            $table->unique(['program_id', 'position']);
        });

        Schema::create('academic_program_contexts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('program_id')->constrained('academic_programs')->cascadeOnDelete();
            $table->string('value', 30);
            $table->unsignedInteger('position');
            $table->timestampsTz();
            $table->unique(['program_id', 'value']);
            $table->unique(['program_id', 'position']);
        });

        Schema::create('academic_program_vehicle_types', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('program_id')->constrained('academic_programs')->cascadeOnDelete();
            $table->string('value', 30);
            $table->unsignedInteger('position');
            $table->timestampsTz();
            $table->unique(['program_id', 'value']);
            $table->unique(['program_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_program_vehicle_types');
        Schema::dropIfExists('academic_program_contexts');
        Schema::dropIfExists('academic_program_license_stages');
        Schema::dropIfExists('academic_program_courses');
        Schema::dropIfExists('academic_programs');
    }
};
