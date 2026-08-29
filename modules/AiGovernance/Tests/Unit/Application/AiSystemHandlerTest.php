<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\AiGovernance\Application\Commands\ApproveAiSystemByCommitteeCommand;
use Modules\AiGovernance\Application\Commands\GrantAiSystemExtraordinaryApprovalCommand;
use Modules\AiGovernance\Application\Commands\PromoteAiSystemCommand;
use Modules\AiGovernance\Application\Commands\RegisterAiSystemCommand;
use Modules\AiGovernance\Application\Exceptions\AiProviderEvaluationNotFound;
use Modules\AiGovernance\Application\Exceptions\AiSystemNotFound;
use Modules\AiGovernance\Application\Exceptions\InvalidAiDataCategory;
use Modules\AiGovernance\Application\Exceptions\InvalidAiRiskLevel;
use Modules\AiGovernance\Application\Exceptions\InvalidAiSupervisionLevel;
use Modules\AiGovernance\Application\Queries\GetAiSystemQuery;
use Modules\AiGovernance\Application\Queries\ListAiSystemsQuery;
use Modules\AiGovernance\Application\Responses\AiSystemResponse;
use Modules\AiGovernance\Application\UseCases\ApproveAiSystemByCommitteeHandler;
use Modules\AiGovernance\Application\UseCases\GetAiSystemHandler;
use Modules\AiGovernance\Application\UseCases\GrantAiSystemExtraordinaryApprovalHandler;
use Modules\AiGovernance\Application\UseCases\ListAiSystemsHandler;
use Modules\AiGovernance\Application\UseCases\PromoteAiSystemHandler;
use Modules\AiGovernance\Application\UseCases\RegisterAiSystemHandler;
use Modules\AiGovernance\Domain\Aggregates\AiProviderEvaluation;
use Modules\AiGovernance\Domain\Aggregates\AiSystem;
use Modules\AiGovernance\Domain\Enums\AiDataCategory;
use Modules\AiGovernance\Domain\Enums\AiRiskLevel;
use Modules\AiGovernance\Domain\Enums\AiSupervisionLevel;
use Modules\AiGovernance\Domain\Exceptions\AiSystemRequiresCommitteeApproval;
use Modules\AiGovernance\Domain\Exceptions\AiSystemRequiresExtraordinaryApproval;
use Modules\AiGovernance\Domain\Exceptions\AiSystemRequiresHumanSupervisionForMinors;
use Modules\AiGovernance\Domain\Repositories\AiProviderEvaluationRepository;
use Modules\AiGovernance\Domain\Repositories\AiSystemRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiProviderEvaluationId;
use Modules\AiGovernance\Domain\ValueObjects\AiSystemId;

final class InMemoryAiSystemRepository implements AiSystemRepository
{
    /** @var array<string, AiSystem> */
    public array $items = [];

    public function save(AiSystem $system): void
    {
        $this->items[$system->id()->value()] = $system;
    }

    public function findById(AiSystemId $id): ?AiSystem
    {
        return $this->items[$id->value()] ?? null;
    }

    /** @return list<AiSystem> */
    public function all(): array
    {
        return array_values($this->items);
    }
}

final class NullAiProviderEvaluationRepository implements AiProviderEvaluationRepository
{
    public function save(AiProviderEvaluation $evaluation): void
    {
        //
    }

    public function findById(AiProviderEvaluationId $id): ?AiProviderEvaluation
    {
        return null;
    }

    /** @return list<AiProviderEvaluation> */
    public function all(): array
    {
        return [];
    }
}

function persistedAiSystemFor(InMemoryAiSystemRepository $repository, AiRiskLevel $riskLevel = AiRiskLevel::Ia1, AiSupervisionLevel $supervisionLevel = AiSupervisionLevel::Recommends, array $dataCategories = [AiDataCategory::Internal]): AiSystem
{
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
    $repository->save($system);

    return $system;
}

it('registra un sistema de IA nuevo', function (): void {
    $repository = new InMemoryAiSystemRepository;
    $evaluations = new NullAiProviderEvaluationRepository;

    $response = (new RegisterAiSystemHandler($repository, $evaluations))->handle(new RegisterAiSystemCommand(
        name: 'Asistente de matricula',
        purpose: 'orientar la matricula',
        functionalOwnerId: (string) Str::uuid(),
        technicalOwnerId: null,
        riskLevel: 'ia1',
        supervisionLevel: 2,
        dataCategories: ['internal'],
        providerEvaluationId: null,
    ));

    expect($response)->toBeInstanceOf(AiSystemResponse::class)
        ->and($response->status)->toBe('evaluation')
        ->and($response->riskLevel)->toBe('ia1');
});

it('rechaza registrar con nivel de riesgo invalido', function (): void {
    $repository = new InMemoryAiSystemRepository;
    $evaluations = new NullAiProviderEvaluationRepository;

    expect(fn () => (new RegisterAiSystemHandler($repository, $evaluations))->handle(new RegisterAiSystemCommand(
        name: 'x', purpose: 'x', functionalOwnerId: (string) Str::uuid(), technicalOwnerId: null,
        riskLevel: 'ia9', supervisionLevel: 1, dataCategories: [], providerEvaluationId: null,
    )))->toThrow(InvalidAiRiskLevel::class);
});

it('rechaza registrar con nivel de supervision invalido', function (): void {
    $repository = new InMemoryAiSystemRepository;
    $evaluations = new NullAiProviderEvaluationRepository;

    expect(fn () => (new RegisterAiSystemHandler($repository, $evaluations))->handle(new RegisterAiSystemCommand(
        name: 'x', purpose: 'x', functionalOwnerId: (string) Str::uuid(), technicalOwnerId: null,
        riskLevel: 'ia1', supervisionLevel: 9, dataCategories: [], providerEvaluationId: null,
    )))->toThrow(InvalidAiSupervisionLevel::class);
});

it('rechaza registrar con categoria de datos invalida', function (): void {
    $repository = new InMemoryAiSystemRepository;
    $evaluations = new NullAiProviderEvaluationRepository;

    expect(fn () => (new RegisterAiSystemHandler($repository, $evaluations))->handle(new RegisterAiSystemCommand(
        name: 'x', purpose: 'x', functionalOwnerId: (string) Str::uuid(), technicalOwnerId: null,
        riskLevel: 'ia1', supervisionLevel: 1, dataCategories: ['inexistente'], providerEvaluationId: null,
    )))->toThrow(InvalidAiDataCategory::class);
});

it('rechaza registrar con una evaluacion de proveedor inexistente', function (): void {
    $repository = new InMemoryAiSystemRepository;
    $evaluations = new NullAiProviderEvaluationRepository;

    expect(fn () => (new RegisterAiSystemHandler($repository, $evaluations))->handle(new RegisterAiSystemCommand(
        name: 'x', purpose: 'x', functionalOwnerId: (string) Str::uuid(), technicalOwnerId: null,
        riskLevel: 'ia1', supervisionLevel: 1, dataCategories: [], providerEvaluationId: (string) Str::uuid(),
    )))->toThrow(AiProviderEvaluationNotFound::class);
});

it('promueve un sistema de IA a piloto y produccion', function (): void {
    $repository = new InMemoryAiSystemRepository;
    $system = persistedAiSystemFor($repository);

    $pilot = (new PromoteAiSystemHandler($repository))->handle(new PromoteAiSystemCommand($system->id()->value(), 'pilot'));
    expect($pilot->status)->toBe('pilot');

    $production = (new PromoteAiSystemHandler($repository))->handle(new PromoteAiSystemCommand($system->id()->value(), 'production'));
    expect($production->status)->toBe('production');
});

it('exige aprobacion extraordinaria para promover un sistema IA-4 a produccion', function (): void {
    $repository = new InMemoryAiSystemRepository;
    $system = persistedAiSystemFor($repository, riskLevel: AiRiskLevel::Ia4, supervisionLevel: AiSupervisionLevel::Automates);
    (new PromoteAiSystemHandler($repository))->handle(new PromoteAiSystemCommand($system->id()->value(), 'pilot'));

    expect(fn () => (new PromoteAiSystemHandler($repository))->handle(new PromoteAiSystemCommand($system->id()->value(), 'production')))
        ->toThrow(AiSystemRequiresExtraordinaryApproval::class);

    (new GrantAiSystemExtraordinaryApprovalHandler($repository))->handle(new GrantAiSystemExtraordinaryApprovalCommand($system->id()->value()));

    expect(fn () => (new PromoteAiSystemHandler($repository))->handle(new PromoteAiSystemCommand($system->id()->value(), 'production')))
        ->toThrow(AiSystemRequiresCommitteeApproval::class);

    (new ApproveAiSystemByCommitteeHandler($repository))->handle(new ApproveAiSystemByCommitteeCommand($system->id()->value()));

    $production = (new PromoteAiSystemHandler($repository))->handle(new PromoteAiSystemCommand($system->id()->value(), 'production'));
    expect($production->status)->toBe('production');
});

it('exige supervision humana suficiente para sistemas que procesan datos de menores', function (): void {
    $repository = new InMemoryAiSystemRepository;
    $system = persistedAiSystemFor($repository, riskLevel: AiRiskLevel::Ia1, supervisionLevel: AiSupervisionLevel::Informs, dataCategories: [AiDataCategory::Minors]);
    (new PromoteAiSystemHandler($repository))->handle(new PromoteAiSystemCommand($system->id()->value(), 'pilot'));

    expect(fn () => (new PromoteAiSystemHandler($repository))->handle(new PromoteAiSystemCommand($system->id()->value(), 'production')))
        ->toThrow(AiSystemRequiresHumanSupervisionForMinors::class);
});

it('rechaza mutar un sistema de IA inexistente', function (): void {
    $repository = new InMemoryAiSystemRepository;
    $id = (string) Str::uuid();

    expect(fn () => (new PromoteAiSystemHandler($repository))->handle(new PromoteAiSystemCommand($id, 'pilot')))
        ->toThrow(AiSystemNotFound::class);
});

it('consulta y lista sistemas de IA', function (): void {
    $repository = new InMemoryAiSystemRepository;
    $system = persistedAiSystemFor($repository);
    persistedAiSystemFor($repository);

    $found = (new GetAiSystemHandler($repository))->handle(new GetAiSystemQuery($system->id()->value()));
    expect($found->id)->toBe($system->id()->value());

    $listed = (new ListAiSystemsHandler($repository))->handle(new ListAiSystemsQuery);
    expect($listed)->toHaveCount(2);
});
