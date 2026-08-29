<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_subscriptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('url', 500);
            $table->text('secret_encrypted');
            $table->jsonb('events');
            $table->string('status', 20);
            $table->timestampsTz();
        });

        Schema::create('webhook_deliveries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('subscription_id')->constrained('webhook_subscriptions')->cascadeOnDelete();
            $table->string('event_name', 100);
            $table->jsonb('payload');
            $table->string('status', 20);
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->dateTimeTz('last_attempted_at')->nullable();
            $table->unsignedSmallInteger('last_response_status')->nullable();
            $table->text('last_response_body')->nullable();
            $table->dateTimeTz('next_retry_at')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_subscriptions');
    }
};
