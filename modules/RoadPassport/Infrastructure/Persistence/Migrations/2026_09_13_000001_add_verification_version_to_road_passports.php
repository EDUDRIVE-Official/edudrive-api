<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('road_passports', function (Blueprint $table): void {
            $table->unsignedInteger('verification_version')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('road_passports', function (Blueprint $table): void {
            $table->dropColumn('verification_version');
        });
    }
};
