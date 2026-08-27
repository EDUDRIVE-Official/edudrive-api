<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stored_files', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('original_filename', 255);
            $table->string('mime_type', 255);
            $table->unsignedBigInteger('size_bytes');
            $table->string('storage_path', 500);
            $table->string('scan_status', 20);
            $table->dateTimeTz('uploaded_at');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stored_files');
    }
};
