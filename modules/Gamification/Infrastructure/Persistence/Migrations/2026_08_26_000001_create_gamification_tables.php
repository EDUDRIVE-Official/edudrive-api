<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->text('description');
            $table->text('earning_rule');
            $table->string('status', 20);
            $table->dateTimeTz('registered_at');
            $table->dateTimeTz('retired_at')->nullable();
            $table->string('retired_reason', 255)->nullable();
            $table->timestampsTz();
        });

        Schema::create('user_achievements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('achievement_id')->constrained('achievements')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('evidence');
            $table->dateTimeTz('earned_at');
            $table->timestampsTz();
            $table->unique(['achievement_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
        Schema::dropIfExists('achievements');
    }
};
