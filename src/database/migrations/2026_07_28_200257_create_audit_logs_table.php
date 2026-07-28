<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('user_id')->nullable()->index();

            $table->string('action', 150)->index();

            $table->string('entity', 100)->nullable()->index();

            $table->string('entity_id', 100)->nullable()->index();

            $table->ipAddress('ip')->nullable();

            $table->text('user_agent')->nullable();

            $table->jsonb('metadata')->nullable();

            $table->timestampTz('occurred_at')->index();

            $table->timestampsTz();

            $table->index(['action', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
