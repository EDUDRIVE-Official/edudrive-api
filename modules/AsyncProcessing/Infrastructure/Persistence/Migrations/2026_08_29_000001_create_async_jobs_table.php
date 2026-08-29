<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('async_jobs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type', 100);
            $table->string('requested_by_user_id', 100)->nullable();
            $table->string('status', 20);
            $table->jsonb('result')->nullable();
            $table->text('failure_reason')->nullable();
            $table->dateTimeTz('started_at')->nullable();
            $table->dateTimeTz('completed_at')->nullable();
            $table->timestampsTz();

            $table->index('requested_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('async_jobs');
    }
};
