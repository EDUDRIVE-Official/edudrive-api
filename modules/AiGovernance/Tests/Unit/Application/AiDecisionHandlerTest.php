<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\AiGovernance\Application\Commands\ApproveAiDecisionCommand;
use Modules\AiGovernance\Application\Commands\RejectAiDecisionCommand;
use Modules\AiGovernance\Application\Exceptions\AiDecisionNotFound;
use Modules\AiGovernance\Application\Exceptions\AiSystemNotFound;
use Modules\AiGovernance\Application\Queries\GetAiDecisionQuery;
use Modules\AiGovernance\Application\Queries\ListAiDecisionsQuery;
use Modules\AiGovernance\Application\UseCases\ApproveAiDecisionHandler;
use Modules\AiGovernance\Application\UseCases\GetAiDecisionHandler;
use Modules\AiGovernance\Application\UseCases\ListAiDecisionsHandler;
use Modules\AiGovernance\Application\UseCases\RejectAiDecisionHandler;
use Modules\AiGovernance\Domain\Entities\AiDecision;
use Modules\AiGovernance\Domain\Exceptions\InvalidAiDecisionReview;
use Modules\AiGovernance\Domain\Repositories\AiDecisionRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiSystemId;

final class InMemoryAiDecisionRepository implements AiDecisionRepository
{
    /** @var array<string, AiDecision> */
    public array $items = [];

    public function save(AiDecision $decision): void
    {
        $this->items[$decision->id()] = $decision;
    }

    public function findById(string $id): ?AiDecision
    {
        return $this->items[$id] ?? null;
    }

    /** @return list<AiDecision> */
    public function findByAiSystem(AiSystemId $aiSystemId): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (AiDecision $decision): bool => $decision->aiSystemId() === $aiSystemId->value(),
        ));
    }
}

function persistedAiDecisionFor(InMemoryAiDecisionRepository $repository, string $aiSystemId, bool $requiresReview = true): AiDecision
{
    $decision = AiDecision::record(
        id: (string) Str::uuid(),
        aiSystemId: $aiSystemId,
        requestedByUserId: (string) Str::uuid(),
        inputSummary: 'entrada de prueba',
        outputSummary: 'salida de prueba',
        requiresReview: $requiresReview,
    );
    $repository->save($decision);

    return $decision;
}

it('aprueba y rechaza decisiones de IA pendientes de revision', function (): void {
    $decisions = new InMemoryAiDecisionRepository;
    $systems = new InMemoryAiSystemRepository;
    $system = persistedAiSystemFor($systems);
    $decision = persistedAiDecisionFor($decisions, $system->id()->value());

    $approved = (new ApproveAiDecisionHandler($decisions))->handle(new ApproveAiDecisionCommand($decision->id(), (string) Str::uuid()));
    expect($approved->reviewStatus)->toBe('approved');

    $anotherDecision = persistedAiDecisionFor($decisions, $system->id()->value());
    $rejected = (new RejectAiDecisionHandler($decisions))->handle(new RejectAiDecisionCommand($anotherDecision->id(), (string) Str::uuid()));
    expect($rejected->reviewStatus)->toBe('rejected');
});

it('rechaza mutar una decision inexistente', function (): void {
    $decisions = new InMemoryAiDecisionRepository;
    $id = (string) Str::uuid();

    expect(fn () => (new ApproveAiDecisionHandler($decisions))->handle(new ApproveAiDecisionCommand($id, (string) Str::uuid())))
        ->toThrow(AiDecisionNotFound::class);
});

it('propaga el rechazo de dominio al aprobar una decision que no requiere revision', function (): void {
    $decisions = new InMemoryAiDecisionRepository;
    $systems = new InMemoryAiSystemRepository;
    $system = persistedAiSystemFor($systems);
    $decision = persistedAiDecisionFor($decisions, $system->id()->value(), requiresReview: false);

    expect(fn () => (new ApproveAiDecisionHandler($decisions))->handle(new ApproveAiDecisionCommand($decision->id(), (string) Str::uuid())))
        ->toThrow(InvalidAiDecisionReview::class);
});

it('consulta y lista decisiones de IA por sistema', function (): void {
    $decisions = new InMemoryAiDecisionRepository;
    $systems = new InMemoryAiSystemRepository;
    $system = persistedAiSystemFor($systems);
    $decision = persistedAiDecisionFor($decisions, $system->id()->value());
    persistedAiDecisionFor($decisions, $system->id()->value());

    $found = (new GetAiDecisionHandler($decisions))->handle(new GetAiDecisionQuery($decision->id()));
    expect($found->id)->toBe($decision->id());

    $listed = (new ListAiDecisionsHandler($systems, $decisions))->handle(new ListAiDecisionsQuery($system->id()->value()));
    expect($listed)->toHaveCount(2);

    expect(fn () => (new ListAiDecisionsHandler($systems, $decisions))->handle(new ListAiDecisionsQuery((string) Str::uuid())))
        ->toThrow(AiSystemNotFound::class);
});
