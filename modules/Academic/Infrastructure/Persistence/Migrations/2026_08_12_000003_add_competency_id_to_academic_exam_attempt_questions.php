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
        Schema::table('academic_exam_attempt_questions', function (Blueprint $table): void {
            $table->uuid('competency_id')->nullable();
        });

        DB::statement('UPDATE academic_exam_attempt_questions
            SET competency_id = (
                SELECT competency_id
                FROM academic_questions
                WHERE academic_questions.id = academic_exam_attempt_questions.question_id
            )
            WHERE competency_id IS NULL');
    }

    public function down(): void
    {
        Schema::table('academic_exam_attempt_questions', function (Blueprint $table): void {
            $table->dropColumn('competency_id');
        });
    }
};
