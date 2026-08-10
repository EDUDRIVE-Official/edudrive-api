<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_course_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('course_id')->constrained('academic_courses')->cascadeOnDelete();
            $table->integer('version_number');
            $table->string('status', 30);
            $table->jsonb('snapshot');
            $table->timestampTz('published_at');
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();
            $table->unique(['course_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_course_versions');
    }
};
