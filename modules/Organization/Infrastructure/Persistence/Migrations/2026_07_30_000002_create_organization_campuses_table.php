<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_campuses', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('organization_id')->index();
            $table->string('name', 180);

            $table->timestampsTz();

            $table
                ->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_campuses');
    }
};
