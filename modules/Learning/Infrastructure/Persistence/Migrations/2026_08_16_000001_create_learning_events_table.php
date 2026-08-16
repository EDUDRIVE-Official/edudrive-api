<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('enrollment_id');
            $table->foreign('enrollment_id')
                ->references('id')
                ->on('academic_enrollments')
                ->cascadeOnDelete();

            $table->uuid('user_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->uuid('course_id');
            $table->foreign('course_id')
                ->references('id')
                ->on('academic_courses')
                ->cascadeOnDelete();

            $table->string('verb', 60);
            $table->string('subject_id');
            $table->jsonb('evidence');
            $table->timestampTz('occurred_at');

            $table->timestampsTz();

            $table->index(['enrollment_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_events');
    }
};
