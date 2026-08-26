<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulation_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('simulator_id')->constrained('simulators')->cascadeOnDelete();
            $table->string('vehicle_type', 100);
            $table->string('scenario', 100);
            $table->dateTimeTz('scheduled_at');
            $table->unsignedInteger('planned_duration_minutes');
            $table->string('status', 20);
            $table->dateTimeTz('started_at')->nullable();
            $table->dateTimeTz('ended_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('simulation_session_history_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('simulation_session_id')->constrained('simulation_sessions')->cascadeOnDelete();
            $table->string('from_status', 20);
            $table->string('to_status', 20);
            $table->string('reason', 255)->nullable();
            $table->dateTimeTz('occurred_at');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_session_history_entries');
        Schema::dropIfExists('simulation_sessions');
    }
};
