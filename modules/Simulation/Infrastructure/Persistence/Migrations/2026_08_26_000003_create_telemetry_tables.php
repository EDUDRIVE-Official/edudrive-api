<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telemetry_samples', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('simulation_session_id')->constrained('simulation_sessions')->cascadeOnDelete();
            $table->float('speed_kph');
            $table->float('braking_percentage');
            $table->float('acceleration_mps2');
            $table->float('steering_angle_degrees');
            $table->dateTimeTz('recorded_at');
            $table->timestampsTz();
        });

        Schema::create('telemetry_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('simulation_session_id')->constrained('simulation_sessions')->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('details', 255)->nullable();
            $table->dateTimeTz('occurred_at');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telemetry_events');
        Schema::dropIfExists('telemetry_samples');
    }
};
