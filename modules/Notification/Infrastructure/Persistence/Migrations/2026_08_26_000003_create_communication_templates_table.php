<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_templates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 50);
            $table->string('locale', 5);
            $table->text('subject_template');
            $table->text('body_template');
            $table->json('variables');
            $table->unsignedInteger('version');
            $table->string('status', 20);
            $table->dateTimeTz('registered_at');
            $table->dateTimeTz('retired_at')->nullable();
            $table->string('retired_reason', 255)->nullable();
            $table->timestampsTz();
            $table->unique(['code', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_templates');
    }
};
