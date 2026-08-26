<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulators', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('device_identifier', 100)->unique();
            $table->string('software_version', 50);
            $table->string('location', 255)->nullable();
            $table->string('status', 20);
            $table->string('integration_key_hash', 64)->unique();
            $table->dateTimeTz('registered_at');
            $table->timestampsTz();
        });

        Schema::create('simulator_history_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('simulator_id')->constrained('simulators')->cascadeOnDelete();
            $table->string('from_status', 20);
            $table->string('to_status', 20);
            $table->string('reason', 255)->nullable();
            $table->dateTimeTz('occurred_at');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulator_history_entries');
        Schema::dropIfExists('simulators');
    }
};
