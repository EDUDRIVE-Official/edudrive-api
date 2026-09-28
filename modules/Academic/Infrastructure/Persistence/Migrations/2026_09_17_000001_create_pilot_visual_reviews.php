<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_pilot_visual_reviews', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('reviewer_user_id')->index();
            $table->string('scene', 30);
            $table->string('scene_version', 30);
            $table->string('specialty', 120);
            $table->date('reviewed_on');
            $table->json('criteria');
            $table->text('findings')->nullable();
            $table->string('status', 40);
            $table->timestampsTz();
            $table->unique(['reviewer_user_id', 'scene', 'scene_version'], 'pilot_visual_review_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_pilot_visual_reviews');
    }
};
