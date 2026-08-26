<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('road_passport_evidence', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('road_passport_id')->constrained('road_passports')->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('subject_id');
            $table->foreignUuid('course_id')->constrained('academic_courses');
            $table->jsonb('details');
            $table->dateTimeTz('occurred_at');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('road_passport_evidence');
    }
};
