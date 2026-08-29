<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_governance_provider_evaluations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('provider_name', 150);
            $table->string('data_location', 150);
            $table->text('retention_policy');
            $table->text('security_review_notes')->nullable();
            $table->string('approval_status', 30);
            $table->dateTimeTz('reviewed_at')->nullable();
            $table->dateTimeTz('next_review_due_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('ai_governance_models', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 150);
            $table->string('provider', 100);
            $table->string('version', 50);
            $table->string('owner_id', 100)->nullable();
            $table->text('use_case')->nullable();
            $table->string('status', 20);
            $table->text('known_risks')->nullable();
            $table->dateTimeTz('registered_at');
            $table->timestampsTz();
        });

        Schema::create('ai_governance_systems', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 150);
            $table->text('purpose');
            $table->string('functional_owner_id', 100);
            $table->string('technical_owner_id', 100)->nullable();
            $table->string('risk_level', 10);
            $table->unsignedTinyInteger('supervision_level');
            $table->jsonb('data_categories');
            $table->string('status', 20);
            $table->boolean('extraordinary_approval_granted')->default(false);
            $table->dateTimeTz('extraordinary_approval_at')->nullable();
            $table->boolean('committee_approved')->default(false);
            $table->dateTimeTz('committee_approved_at')->nullable();
            $table->foreignUuid('provider_evaluation_id')->nullable()->constrained('ai_governance_provider_evaluations')->nullOnDelete();
            $table->dateTimeTz('registered_at');
            $table->timestampsTz();
        });

        Schema::create('ai_governance_decisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('ai_system_id')->constrained('ai_governance_systems')->cascadeOnDelete();
            $table->string('requested_by_user_id', 100)->nullable();
            $table->text('input_summary');
            $table->text('output_summary');
            $table->float('confidence_level')->nullable();
            $table->unsignedInteger('tokens_input')->nullable();
            $table->unsignedInteger('tokens_output')->nullable();
            $table->decimal('cost_amount', 10, 6)->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('review_status', 20);
            $table->string('reviewed_by_user_id', 100)->nullable();
            $table->dateTimeTz('reviewed_at')->nullable();
            $table->dateTimeTz('occurred_at');
            $table->timestampsTz();
        });

        Schema::create('ai_governance_prompts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('identifier', 150)->unique();
            $table->text('purpose');
            $table->foreignUuid('model_id')->nullable()->constrained('ai_governance_models')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('author_id', 100)->nullable();
            $table->text('content');
            $table->string('status', 20);
            $table->timestampsTz();
        });

        Schema::create('ai_governance_incidents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('ai_system_id')->constrained('ai_governance_systems')->cascadeOnDelete();
            $table->string('severity', 20);
            $table->text('description');
            $table->string('status', 20);
            $table->text('corrective_actions')->nullable();
            $table->dateTimeTz('discovered_at');
            $table->dateTimeTz('resolved_at')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_governance_incidents');
        Schema::dropIfExists('ai_governance_prompts');
        Schema::dropIfExists('ai_governance_decisions');
        Schema::dropIfExists('ai_governance_systems');
        Schema::dropIfExists('ai_governance_models');
        Schema::dropIfExists('ai_governance_provider_evaluations');
    }
};
