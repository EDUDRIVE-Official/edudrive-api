<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decision_points', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('simulation_session_id')->constrained('simulation_sessions')->cascadeOnDelete();
            $table->string('road_context', 255);
            $table->string('risk_level', 20);
            $table->string('driver_reaction', 20);
            $table->dateTimeTz('occurred_at');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decision_points');
    }
};
