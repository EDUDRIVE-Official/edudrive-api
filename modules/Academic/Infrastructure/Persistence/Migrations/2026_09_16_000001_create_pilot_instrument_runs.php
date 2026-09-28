<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_pilot_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('owner_user_id')->index();
            $table->string('form', 30);
            $table->string('instrument_version', 30);
            $table->string('status', 20)->default('open');
            $table->unsignedInteger('revision')->default(0);
            $table->json('items');
            $table->json('events');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_pilot_runs');
    }
};
