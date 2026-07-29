<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_courses', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->string('code', 50)->unique();
            $table->string('title', 180);
            $table->text('description')->nullable();

            $table->string('status', 30)->index();

            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('archived_at')->nullable();

            $table->timestampsTz();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_courses');
    }
};
