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

        Schema::create('academic_questions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('competency_id')->constrained('academic_competencies')->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('prompt', 1000);
            $table->string('explanation', 2000)->nullable();
            $table->integer('score');
            $table->json('media')->nullable();
            $table->json('response');
            $table->timestampsTz();
        });

        Schema::create('academic_question_options', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('question_id')->constrained('academic_questions')->cascadeOnDelete();
            $table->string('ref_id', 80);
            $table->string('side', 10)->nullable();
            $table->string('label', 500);
            $table->integer('position');
            $table->timestampsTz();
            $table->unique(['question_id', 'ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_question_options');
        Schema::dropIfExists('academic_questions');
    }
};
