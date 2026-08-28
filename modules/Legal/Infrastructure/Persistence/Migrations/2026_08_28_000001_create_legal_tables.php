<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_consent_policies', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('key', 100)->index();
            $table->unsignedInteger('version');
            $table->dateTimeTz('effective_at');
            $table->timestampsTz();
            $table->unique(['key', 'version']);
        });

        Schema::create('legal_user_consents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('policy_key', 100)->index();
            $table->unsignedInteger('policy_version');
            $table->dateTimeTz('accepted_at');
            $table->timestampsTz();
            $table->unique(['user_id', 'policy_key', 'policy_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_user_consents');
        Schema::dropIfExists('legal_consent_policies');
    }
};
