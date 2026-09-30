<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_pilot_visual_candidates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('scene', 30);
            $table->string('scene_version', 30);
            $table->foreignUuid('course_id')->constrained('academic_courses')->cascadeOnDelete();
            $table->foreignUuid('lesson_id')->constrained('academic_lessons')->cascadeOnDelete();
            $table->uuid('linked_by')->index();
            $table->timestampTz('linked_at');
            $table->timestampsTz();
            $table->unique(['scene', 'scene_version'], 'pilot_visual_candidate_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_pilot_visual_candidates');
    }
};
