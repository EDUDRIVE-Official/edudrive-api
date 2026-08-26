<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_enrollments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('course_id')->index();
            $table->uuid('user_id')->index();
            $table->uuid('organization_id')->nullable()->index();
            $table->string('status', 30);
            $table->string('source', 30);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('enrolled_at');
            $table->timestamps();

            $table->foreign('course_id')
                ->references('id')
                ->on('academic_courses')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->index(['course_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_enrollments');
    }
};
