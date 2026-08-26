<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('course_id')->constrained('academic_courses')->cascadeOnDelete();
            $table->string('validation_code', 14)->unique();
            $table->string('status', 20);
            $table->dateTimeTz('issued_at');
            $table->dateTimeTz('expires_at')->nullable();
            $table->timestampsTz();
            $table->unique(['user_id', 'course_id']);
        });

        Schema::create('certificate_history_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('certificate_id')->constrained('certificates')->cascadeOnDelete();
            $table->string('from_status', 20);
            $table->string('to_status', 20);
            $table->string('reason', 255)->nullable();
            $table->dateTimeTz('occurred_at');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_history_entries');
        Schema::dropIfExists('certificates');
    }
};
