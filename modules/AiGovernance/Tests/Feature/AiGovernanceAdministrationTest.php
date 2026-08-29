<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\AiGovernance\Domain\Aggregates\AiProviderEvaluation;
use Modules\AiGovernance\Domain\Aggregates\AiSystem;
use Modules\AiGovernance\Domain\Entities\AiDecision;
use Modules\AiGovernance\Domain\Enums\AiDataCategory;
use Modules\AiGovernance\Domain\Enums\AiRiskLevel;
use Modules\AiGovernance\Domain\Enums\AiSupervisionLevel;
use Modules\AiGovernance\Domain\Repositories\AiDecisionRepository;
use Modules\AiGovernance\Domain\Repositories\AiProviderEvaluationRepository;
use Modules\AiGovernance\Domain\Repositories\AiSystemRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiProviderEvaluationId;
use Modules\AiGovernance\Domain\ValueObjects\AiSystemId;
use Modules\Authorization\Domain\Enums\Role;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedAiSystemFeature(
    AiRiskLevel $riskLevel = AiRiskLevel::Ia1,
    AiSupervisionLevel $supervisionLevel = AiSupervisionLevel::Recommends,
    array $dataCategories = [AiDataCategory::Internal],
): AiSystem {
    $system = AiSystem::register(
        id: AiSystemId::fromString((string) Str::uuid()),
        name: 'Recomendador de rutas',
        purpose: 'sugerir rutas de aprendizaje',
        functionalOwnerId: (string) Str::uuid(),
        technicalOwnerId: null,
        riskLevel: $riskLevel,
        supervisionLevel: $supervisionLevel,
        dataCategories: $dataCategories,
    );
    app(AiSystemRepository::class)->save($system);

    return $system;
}

function persistedAiProviderEvaluationFeature(): AiProviderEvaluation
{
    $evaluation = AiProviderEvaluation::register(
        id: AiProviderEvaluationId::fromString((string) Str::uuid()),
        providerName: 'OpenAI',
        dataLocation: 'Estados Unidos',
        retentionPolicy: '30 dias',
    );
    app(AiProviderEvaluationRepository::class)->save($evaluation);

    return $evaluation;
}

it('requiere autenticacion para todos los endpoints protegidos', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/ai-governance/systems')->assertUnauthorized();
    $this->postJson('/api/v1/ai-governance/systems', [])->assertUnauthorized();
});

it('rechaza registrar un sistema de IA sin el permiso de gestion', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);

    $this->postJson('/api/v1/ai-governance/systems', [
        'name' => 'x', 'purpose' => 'x', 'functional_owner_id' => (string) Str::uuid(),
        'risk_level' => 'ia1', 'supervision_level' => 1, 'data_categories' => [],
    ])->assertStatus(403);
});

it('registra un proveedor de IA y lo aprueba via el endpoint administrativo', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $response = $this->postJson('/api/v1/ai-governance/provider-evaluations', [
        'provider_name' => 'Anthropic',
        'data_location' => 'Estados Unidos',
        'retention_policy' => 'sin retencion',
    ])
        ->assertCreated()
        ->assertJsonPath('data.approval_status', 'pending_review');

    $evaluationId = $response->json('data.id');

    $this->postJson("/api/v1/ai-governance/provider-evaluations/{$evaluationId}/approve", [])
        ->assertOk()
        ->assertJsonPath('data.approval_status', 'approved');

    $this->getJson('/api/v1/ai-governance/provider-evaluations')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('registra un sistema de IA y lo consulta via el endpoint administrativo', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $response = $this->postJson('/api/v1/ai-governance/systems', [
        'name' => 'Asistente de matricula',
        'purpose' => 'orientar la matricula',
        'functional_owner_id' => (string) Str::uuid(),
        'risk_level' => 'ia1',
        'supervision_level' => 2,
        'data_categories' => ['internal'],
    ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'evaluation');

    $aiSystemId = $response->json('data.id');

    $this->getJson("/api/v1/ai-governance/systems/{$aiSystemId}")
        ->assertOk()
        ->assertJsonPath('data.name', 'Asistente de matricula');
});

it('rechaza promover a produccion un sistema IA-4 sin aprobacion extraordinaria', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $system = persistedAiSystemFeature(riskLevel: AiRiskLevel::Ia4, supervisionLevel: AiSupervisionLevel::Automates);

    $this->postJson("/api/v1/ai-governance/systems/{$system->id()->value()}/promote", ['status' => 'pilot'])
        ->assertOk();

    $this->postJson("/api/v1/ai-governance/systems/{$system->id()->value()}/promote", ['status' => 'production'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'AI_SYSTEM_REQUIRES_EXTRAORDINARY_APPROVAL');
});

it('promueve a produccion tras otorgar la aprobacion extraordinaria y del comite', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $system = persistedAiSystemFeature(riskLevel: AiRiskLevel::Ia4, supervisionLevel: AiSupervisionLevel::Automates);
    $this->postJson("/api/v1/ai-governance/systems/{$system->id()->value()}/promote", ['status' => 'pilot']);

    $this->postJson("/api/v1/ai-governance/systems/{$system->id()->value()}/grant-extraordinary-approval", [])
        ->assertOk()
        ->assertJsonPath('data.extraordinary_approval_granted', true);

    $this->postJson("/api/v1/ai-governance/systems/{$system->id()->value()}/approve-by-committee", [])
        ->assertOk()
        ->assertJsonPath('data.committee_approved', true);

    $this->postJson("/api/v1/ai-governance/systems/{$system->id()->value()}/promote", ['status' => 'production'])
        ->assertOk()
        ->assertJsonPath('data.status', 'production');
});

it('reporta un incidente de IA y lo resuelve via el endpoint administrativo', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $system = persistedAiSystemFeature();

    $response = $this->postJson('/api/v1/ai-governance/incidents', [
        'ai_system_id' => $system->id()->value(),
        'severity' => 'high',
        'description' => 'el modelo genero una respuesta sesgada',
    ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'open');

    $incidentId = $response->json('data.id');

    $this->postJson("/api/v1/ai-governance/incidents/{$incidentId}/resolve", [
        'corrective_actions' => 'se ajusto el prompt del modelo',
    ])
        ->assertOk()
        ->assertJsonPath('data.status', 'resolved');

    $this->getJson("/api/v1/ai-governance/systems/{$system->id()->value()}/incidents")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('aprueba y rechaza decisiones de IA pendientes de revision humana via el endpoint administrativo', function (): void {
    /** @var TestCase $this */
    $admin = actingAsRole(Role::SuperAdmin);
    $system = persistedAiSystemFeature(supervisionLevel: AiSupervisionLevel::Proposes);

    $decision = AiDecision::record(
        id: (string) Str::uuid(),
        aiSystemId: $system->id()->value(),
        requestedByUserId: null,
        inputSummary: 'entrada',
        outputSummary: 'salida',
        requiresReview: true,
    );
    app(AiDecisionRepository::class)->save($decision);

    $this->postJson("/api/v1/ai-governance/decisions/{$decision->id()}/approve", [])
        ->assertOk()
        ->assertJsonPath('data.review_status', 'approved')
        ->assertJsonPath('data.reviewed_by_user_id', (string) $admin->id);

    $anotherDecision = AiDecision::record(
        id: (string) Str::uuid(),
        aiSystemId: $system->id()->value(),
        requestedByUserId: null,
        inputSummary: 'entrada',
        outputSummary: 'salida',
        requiresReview: true,
    );
    app(AiDecisionRepository::class)->save($anotherDecision);

    $this->postJson("/api/v1/ai-governance/decisions/{$anotherDecision->id()}/reject", [])
        ->assertOk()
        ->assertJsonPath('data.review_status', 'rejected');
});

it('rechaza consultar el modulo sin el permiso de vista', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Student);

    $this->getJson('/api/v1/ai-governance/systems')->assertStatus(403);
});
