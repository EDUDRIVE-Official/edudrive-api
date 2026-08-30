<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_groups', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('course_id')->index();
            $table->uuid('organization_id')->nullable()->index();
            $table->string('name', 150);
            $table->uuid('teacher_id')->nullable()->index();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamps();

            $table->foreign('course_id')
                ->references('id')
                ->on('academic_courses')
                ->cascadeOnDelete();

            $table->foreign('teacher_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_groups');
    }
};
