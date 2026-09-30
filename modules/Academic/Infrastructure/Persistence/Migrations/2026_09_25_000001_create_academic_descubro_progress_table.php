<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_descubro_progress', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('experience', 50);
            $table->string('content_version', 30);
            $table->string('criterion_version', 40);
            $table->unsignedInteger('revision');
            $table->json('state');
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();
            $table->unique(['user_id', 'experience', 'content_version'], 'descubro_owner_experience_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_descubro_progress');
    }
};
