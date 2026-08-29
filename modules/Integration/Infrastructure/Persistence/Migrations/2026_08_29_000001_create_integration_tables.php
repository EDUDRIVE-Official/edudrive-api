<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_api_consumers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 150);
            $table->jsonb('scopes');
            $table->string('status', 20);
            $table->string('integration_key_hash', 64)->unique();
            $table->dateTimeTz('expires_at')->nullable();
            $table->dateTimeTz('issued_at');
            $table->timestampsTz();
        });

        Schema::create('integration_api_consumer_history_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('api_consumer_id')->constrained('integration_api_consumers')->cascadeOnDelete();
            $table->string('from_status', 20);
            $table->string('to_status', 20);
            $table->string('reason', 255)->nullable();
            $table->dateTimeTz('occurred_at');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_api_consumer_history_entries');
        Schema::dropIfExists('integration_api_consumers');
    }
};
