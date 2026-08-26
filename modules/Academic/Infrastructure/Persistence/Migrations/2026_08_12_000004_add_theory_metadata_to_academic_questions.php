<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_questions', function (Blueprint $table): void {
            $table->string('source_kind', 30)->default('custom');
            $table->string('source_reference', 255)->nullable();

            if (DB::connection()->getDriverName() === 'pgsql') {
                $table->jsonb('license_categories')->default(DB::raw("'[]'::jsonb"));

                return;
            }

            $table->json('license_categories')->default('[]');
        });
    }

    public function down(): void
    {
        Schema::table('academic_questions', function (Blueprint $table): void {
            $table->dropColumn(['source_kind', 'source_reference', 'license_categories']);
        });
    }
};
