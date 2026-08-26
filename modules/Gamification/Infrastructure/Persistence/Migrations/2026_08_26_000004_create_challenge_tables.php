<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->text('description');
            $table->string('type', 20);
            $table->text('reward');
            $table->dateTimeTz('starts_at');
            $table->dateTimeTz('ends_at');
            $table->string('status', 20);
            $table->dateTimeTz('registered_at');
            $table->dateTimeTz('retired_at')->nullable();
            $table->string('retired_reason', 255)->nullable();
            $table->timestampsTz();
        });

        Schema::create('challenge_participations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('challenge_id')->constrained('challenges')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20);
            $table->dateTimeTz('joined_at');
            $table->dateTimeTz('completed_at')->nullable();
            $table->text('evidence')->nullable();
            $table->timestampsTz();
            $table->unique(['challenge_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenge_participations');
        Schema::dropIfExists('challenges');
    }
};
