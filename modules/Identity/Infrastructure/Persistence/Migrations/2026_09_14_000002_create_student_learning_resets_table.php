<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_student_learning_resets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('performed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->jsonb('summary');
            $table->longText('encrypted_snapshot');
            $table->dateTimeTz('reset_at');
            $table->timestampsTz();
            $table->index(['user_id', 'reset_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_student_learning_resets');
    }
};
