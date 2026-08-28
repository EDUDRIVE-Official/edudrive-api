<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
        });

        Schema::table('certificates', function (Blueprint $table): void {
            $table->uuid('user_id')->nullable()->change();
        });

        Schema::table('certificates', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
        });

        Schema::table('certificates', function (Blueprint $table): void {
            $table->uuid('user_id')->nullable(false)->change();
        });

        Schema::table('certificates', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
