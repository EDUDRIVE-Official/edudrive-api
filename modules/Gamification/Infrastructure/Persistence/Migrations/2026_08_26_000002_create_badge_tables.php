<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->text('description');
            $table->text('criteria');
            $table->string('category', 20);
            $table->string('level', 20);
            $table->unsignedInteger('version');
            $table->string('status', 20);
            $table->dateTimeTz('registered_at');
            $table->dateTimeTz('retired_at')->nullable();
            $table->string('retired_reason', 255)->nullable();
            $table->timestampsTz();
        });

        Schema::create('user_badges', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('badge_id')->constrained('badges')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('awarded_version');
            $table->text('evidence');
            $table->dateTimeTz('earned_at');
            $table->timestampsTz();
            $table->unique(['badge_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('badges');
    }
};
