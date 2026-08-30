<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_user_consents', function (Blueprint $table): void {
            $table->dateTimeTz('revoked_at')->nullable()->after('accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('legal_user_consents', function (Blueprint $table): void {
            $table->dropColumn('revoked_at');
        });
    }
};
