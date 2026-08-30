<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardian_relationships', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('guardian_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('minor_user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTimeTz('created_at');
            $table->dateTimeTz('revoked_at')->nullable();
            $table->index(['guardian_user_id', 'minor_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_relationships');
    }
};
