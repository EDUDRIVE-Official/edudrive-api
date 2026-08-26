<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('road_passports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('status', 20);
            $table->integer('level');
            $table->dateTimeTz('issued_at');
            $table->timestampsTz();
        });

        Schema::create('road_passport_history_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('road_passport_id')->constrained('road_passports')->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('from_value', 30);
            $table->string('to_value', 30);
            $table->string('reason', 255)->nullable();
            $table->dateTimeTz('occurred_at');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('road_passport_history_entries');
        Schema::dropIfExists('road_passports');
    }
};
