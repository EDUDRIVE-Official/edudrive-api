<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_pilot_visual_reviewer_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('specialty', 40);
            $table->string('organization', 180);
            $table->text('qualification');
            $table->string('evidence_reference', 500);
            $table->uuid('designated_by')->index();
            $table->date('designated_on');
            $table->boolean('active');
            $table->timestampsTz();
            $table->index(['user_id', 'created_at']);
        });

        Schema::table('academic_pilot_visual_reviewers', function (Blueprint $table): void {
            $table->foreignUuid('current_event_id')->nullable()->after('user_id')
                ->constrained('academic_pilot_visual_reviewer_events')->nullOnDelete();
        });

        Schema::table('academic_pilot_visual_reviews', function (Blueprint $table): void {
            $table->foreignUuid('reviewer_designation_event_id')->nullable()
                ->after('reviewer_user_id')
                ->constrained('academic_pilot_visual_reviewer_events')
                ->nullOnDelete();
        });

        foreach (DB::table('academic_pilot_visual_reviewers')->get() as $reviewer) {
            $eventId = (string) Str::uuid();
            DB::table('academic_pilot_visual_reviewer_events')->insert([
                'id' => $eventId,
                'user_id' => $reviewer->user_id,
                'specialty' => $reviewer->specialty,
                'organization' => $reviewer->organization,
                'qualification' => $reviewer->qualification,
                'evidence_reference' => $reviewer->evidence_reference,
                'designated_by' => $reviewer->designated_by,
                'designated_on' => $reviewer->designated_on,
                'active' => $reviewer->active,
                'created_at' => $reviewer->created_at,
                'updated_at' => $reviewer->updated_at,
            ]);
            DB::table('academic_pilot_visual_reviewers')->where('id', $reviewer->id)->update(['current_event_id' => $eventId]);
        }
    }

    public function down(): void
    {
        Schema::table('academic_pilot_visual_reviews', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reviewer_designation_event_id');
        });
        Schema::table('academic_pilot_visual_reviewers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('current_event_id');
        });
        Schema::dropIfExists('academic_pilot_visual_reviewer_events');
    }
};
