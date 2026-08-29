<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\AiGovernance\Domain\Aggregates\AiSystem;
use Modules\AiGovernance\Domain\Enums\AiRiskLevel;
use Modules\AiGovernance\Domain\Enums\AiSupervisionLevel;
use Modules\AiGovernance\Domain\Enums\AiSystemStatus;
use Modules\AiGovernance\Domain\Repositories\AiDecisionRepository;
use Modules\AiGovernance\Domain\Repositories\AiSystemRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiSystemId;
use Modules\Authorization\Domain\Enums\Role;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedUsableAiSystemForGateway(AiSupervisionLevel $supervisionLevel = AiSupervisionLevel::Recommends): AiSystem
{
    $system = AiSystem::register(
        id: AiSystemId::fromString((string) Str::uuid()),
        name: 'Asistente de resumen',
        purpose: 'resumir contenido academico',
        functionalOwnerId: (string) Str::uuid(),
        technicalOwnerId: null,
        riskLevel: AiRiskLevel::Ia1,
        supervisionLevel: $supervisionLevel,
        dataCategories: [],
    );
    $system->promoteTo(AiSystemStatus::Pilot, new DateTimeImmutable('now'));
    app(AiSystemRepository::class)->save($system);

    return $system;
}

it('invoca el gateway de IA, registra una decision real y no requiere revision para supervision baja', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $system = persistedUsableAiSystemForGateway(AiSupervisionLevel::Informs);
    Http::fake(['*' => Http::response([
        'output' => 'resumen generado',
        'tokens_input' => 10,
        'tokens_output' => 20,
        'cost_amount' => 0.02,
    ], 200)]);

    $this->postJson('/api/v1/ai-governance/gateway/invoke', [
        'ai_system_id' => $system->id()->value(),
        'input' => 'texto de entrada',
    ])
        ->assertOk()
        ->assertJsonPath('data.output', 'resumen generado')
        ->assertJsonPath('data.review_status', 'not_required')
        ->assertJsonPath('data.tokens_input', 10);

    expect(app(AiDecisionRepository::class)->findByAiSystem($system->id()))->toHaveCount(1);
});

it('marca la decision como pendiente de revision cuando el sistema tiene supervision alta', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $system = persistedUsableAiSystemForGateway(AiSupervisionLevel::Proposes);
    Http::fake(['*' => Http::response(['output' => 'respuesta'], 200)]);

    $this->postJson('/api/v1/ai-governance/gateway/invoke', [
        'ai_system_id' => $system->id()->value(),
        'input' => 'texto de entrada',
    ])
        ->assertOk()
        ->assertJsonPath('data.review_status', 'pending');
});

it('rechaza invocar el gateway para un sistema de IA que no esta en piloto ni produccion', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $system = AiSystem::register(
        id: AiSystemId::fromString((string) Str::uuid()),
        name: 'Sistema en evaluacion',
        purpose: 'x',
        functionalOwnerId: (string) Str::uuid(),
        technicalOwnerId: null,
        riskLevel: AiRiskLevel::Ia1,
        supervisionLevel: AiSupervisionLevel::Informs,
        dataCategories: [],
    );
    app(AiSystemRepository::class)->save($system);
    Http::fake();

    $this->postJson('/api/v1/ai-governance/gateway/invoke', [
        'ai_system_id' => $system->id()->value(),
        'input' => 'texto de entrada',
    ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'AI_SYSTEM_NOT_USABLE');

    Http::assertNothingSent();
});

it('limita las invocaciones del gateway a 30 por minuto por usuario', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    $system = persistedUsableAiSystemForGateway();
    Http::fake(['*' => Http::response(['output' => 'respuesta'], 200)]);

    for ($attempt = 1; $attempt <= 30; $attempt++) {
        $this->postJson('/api/v1/ai-governance/gateway/invoke', [
            'ai_system_id' => $system->id()->value(),
            'input' => 'texto de entrada',
        ]);
    }

    $this->postJson('/api/v1/ai-governance/gateway/invoke', [
        'ai_system_id' => $system->id()->value(),
        'input' => 'texto de entrada',
    ])
        ->assertStatus(429);
});
