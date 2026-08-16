<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_enrollment_lesson_completions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('enrollment_id')->index();
            $table->uuid('lesson_id')->index();
            $table->timestamp('completed_at');
            $table->integer('time_spent_minutes')->nullable();
            $table->timestamps();

            $table->foreign('enrollment_id')
                ->references('id')
                ->on('academic_enrollments')
                ->cascadeOnDelete();

            $table->unique(['enrollment_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_enrollment_lesson_completions');
    }
};
