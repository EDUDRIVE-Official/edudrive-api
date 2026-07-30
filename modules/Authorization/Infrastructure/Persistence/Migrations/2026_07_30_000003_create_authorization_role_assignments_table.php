<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authorization_role_assignments', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->uuid('user_id')->index();
            $table->string('role', 30)->index();
            $table->uuid('organization_id')->nullable()->index();

            $table->timestampTz('assigned_at')->index();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authorization_role_assignments');
    }
};
