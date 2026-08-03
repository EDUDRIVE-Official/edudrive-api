<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_competencies', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 60)->unique();
            $table->string('title', 180);
            $table->text('description');
            $table->string('category', 50);
            $table->string('mastery_level', 30);
            $table->string('status', 30)->index();
            $table->timestampsTz();
        });

        Schema::create('academic_subcompetencies', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('competency_id')->constrained('academic_competencies')->cascadeOnDelete();
            $table->string('code', 70)->unique();
            $table->string('title', 180);
            $table->unsignedInteger('position');
            $table->timestampsTz();
            $table->unique(['competency_id', 'position']);
        });

        Schema::create('academic_competency_indicators', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('subcompetency_id')->constrained('academic_subcompetencies')->cascadeOnDelete();
            $table->string('code', 80);
            $table->text('description');
            $table->unsignedInteger('position');
            $table->timestampsTz();
            $table->unique(['subcompetency_id', 'code']);
            $table->unique(['subcompetency_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_competency_indicators');
        Schema::dropIfExists('academic_subcompetencies');
        Schema::dropIfExists('academic_competencies');
    }
};
