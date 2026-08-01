<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->string('name', 180);
            $table->string('type', 30)->index();

            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
