<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_courses', function (Blueprint $table): void {
            $table->text('objectives')->nullable()->after('description');
            $table->text('prerequisites')->nullable()->after('objectives');
            $table->string('modality', 30)->nullable()->after('prerequisites');
            $table->unsignedInteger('duration_hours')->nullable()->after('modality');
        });
    }

    public function down(): void
    {
        Schema::table('academic_courses', function (Blueprint $table): void {
            $table->dropColumn(['objectives', 'prerequisites', 'modality', 'duration_hours']);
        });
    }
};
