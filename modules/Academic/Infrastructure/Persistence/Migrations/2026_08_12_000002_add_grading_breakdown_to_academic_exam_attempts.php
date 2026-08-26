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
        Schema::table('academic_exam_attempts', function (Blueprint $table): void {
            if (DB::connection()->getDriverName() === 'pgsql') {
                $table->jsonb('grading_breakdown')->nullable();
                $table->jsonb('competency_results')->nullable();

                return;
            }

            $table->json('grading_breakdown')->nullable();
            $table->json('competency_results')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('academic_exam_attempts', function (Blueprint $table): void {
            $table->dropColumn(['grading_breakdown', 'competency_results']);
        });
    }
};
