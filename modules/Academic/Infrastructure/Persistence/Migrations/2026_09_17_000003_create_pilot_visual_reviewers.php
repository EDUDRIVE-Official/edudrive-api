<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_pilot_visual_reviewers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('specialty', 40);
            $table->string('organization', 180);
            $table->text('qualification');
            $table->string('evidence_reference', 500);
            $table->uuid('designated_by')->index();
            $table->date('designated_on');
            $table->boolean('active')->default(true)->index();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_pilot_visual_reviewers');
    }
};
