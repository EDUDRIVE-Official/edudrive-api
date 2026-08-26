<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_exams', function (Blueprint $table): void {
            $table->string('kind', 30)->default('standard');
            $table->string('license_category', 50)->nullable();
            $table->boolean('allow_partial_credit')->default(false);
            $table->boolean('apply_penalties')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('academic_exams', function (Blueprint $table): void {
            $table->dropColumn(['kind', 'license_category', 'allow_partial_credit', 'apply_penalties']);
        });
    }
};
