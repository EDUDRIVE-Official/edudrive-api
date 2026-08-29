<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\AiGovernance\Application\Commands\ApproveAiProviderEvaluationCommand;
use Modules\AiGovernance\Application\Commands\RegisterAiProviderEvaluationCommand;
use Modules\AiGovernance\Application\Commands\RejectAiProviderEvaluationCommand;
use Modules\AiGovernance\Application\Commands\RequireAiProviderReevaluationCommand;
use Modules\AiGovernance\Application\Exceptions\AiProviderEvaluationNotFound;
use Modules\AiGovernance\Application\Queries\GetAiProviderEvaluationQuery;
use Modules\AiGovernance\Application\Queries\ListAiProviderEvaluationsQuery;
use Modules\AiGovernance\Application\Responses\AiProviderEvaluationResponse;
use Modules\AiGovernance\Application\UseCases\ApproveAiProviderEvaluationHandler;
use Modules\AiGovernance\Application\UseCases\GetAiProviderEvaluationHandler;
use Modules\AiGovernance\Application\UseCases\ListAiProviderEvaluationsHandler;
use Modules\AiGovernance\Application\UseCases\RegisterAiProviderEvaluationHandler;
use Modules\AiGovernance\Application\UseCases\RejectAiProviderEvaluationHandler;
use Modules\AiGovernance\Application\UseCases\RequireAiProviderReevaluationHandler;
use Modules\AiGovernance\Domain\Aggregates\AiProviderEvaluation;
use Modules\AiGovernance\Domain\Exceptions\InvalidAiProviderEvaluationTransition;
use Modules\AiGovernance\Domain\Repositories\AiProviderEvaluationRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiProviderEvaluationId;

final class InMemoryAiProviderEvaluationRepository implements AiProviderEvaluationRepository
{
    /** @var array<string, AiProviderEvaluation> */
    public array $items = [];

    public function save(AiProviderEvaluation $evaluation): void
    {
        $this->items[$evaluation->id()->value()] = $evaluation;
    }

    public function findById(AiProviderEvaluationId $id): ?AiProviderEvaluation
    {
        return $this->items[$id->value()] ?? null;
    }

    /** @return list<AiProviderEvaluation> */
    public function all(): array
    {
        return array_values($this->items);
    }
}

function persistedAiProviderEvaluationFor(InMemoryAiProviderEvaluationRepository $repository): AiProviderEvaluation
{
    $evaluation = AiProviderEvaluation::register(
        id: AiProviderEvaluationId::fromString((string) Str::uuid()),
        providerName: 'OpenAI',
        dataLocation: 'Estados Unidos',
        retentionPolicy: '30 dias',
    );
    $repository->save($evaluation);

    return $evaluation;
}

it('registra una evaluacion de proveedor nueva', function (): void {
    $repository = new InMemoryAiProviderEvaluationRepository;

    $response = (new RegisterAiProviderEvaluationHandler($repository))->handle(new RegisterAiProviderEvaluationCommand(
        providerName: 'Anthropic',
        dataLocation: 'Estados Unidos',
        retentionPolicy: 'sin retencion',
        securityReviewNotes: null,
    ));

    expect($response)->toBeInstanceOf(AiProviderEvaluationResponse::class)
        ->and($response->providerName)->toBe('Anthropic')
        ->and($response->approvalStatus)->toBe('pending_review');
});

it('aprueba, rechaza y marca para reevaluacion una evaluacion existente', function (): void {
    $repository = new InMemoryAiProviderEvaluationRepository;
    $evaluation = persistedAiProviderEvaluationFor($repository);

    $approved = (new ApproveAiProviderEvaluationHandler($repository))->handle(new ApproveAiProviderEvaluationCommand($evaluation->id()->value(), null));
    expect($approved->approvalStatus)->toBe('approved');

    $reevaluate = (new RequireAiProviderReevaluationHandler($repository))->handle(new RequireAiProviderReevaluationCommand($evaluation->id()->value()));
    expect($reevaluate->approvalStatus)->toBe('requires_reevaluation');

    $anotherEvaluation = persistedAiProviderEvaluationFor($repository);
    $rejected = (new RejectAiProviderEvaluationHandler($repository))->handle(new RejectAiProviderEvaluationCommand($anotherEvaluation->id()->value()));
    expect($rejected->approvalStatus)->toBe('rejected');
});

it('rechaza mutar una evaluacion inexistente', function (): void {
    $repository = new InMemoryAiProviderEvaluationRepository;
    $id = (string) Str::uuid();

    expect(fn () => (new ApproveAiProviderEvaluationHandler($repository))->handle(new ApproveAiProviderEvaluationCommand($id, null)))
        ->toThrow(AiProviderEvaluationNotFound::class);
});

it('propaga el rechazo de dominio al aprobar dos veces', function (): void {
    $repository = new InMemoryAiProviderEvaluationRepository;
    $evaluation = persistedAiProviderEvaluationFor($repository);
    (new ApproveAiProviderEvaluationHandler($repository))->handle(new ApproveAiProviderEvaluationCommand($evaluation->id()->value(), null));

    expect(fn () => (new ApproveAiProviderEvaluationHandler($repository))->handle(new ApproveAiProviderEvaluationCommand($evaluation->id()->value(), null)))
        ->toThrow(InvalidAiProviderEvaluationTransition::class);
});

it('consulta y lista evaluaciones de proveedor', function (): void {
    $repository = new InMemoryAiProviderEvaluationRepository;
    $evaluation = persistedAiProviderEvaluationFor($repository);
    persistedAiProviderEvaluationFor($repository);

    $found = (new GetAiProviderEvaluationHandler($repository))->handle(new GetAiProviderEvaluationQuery($evaluation->id()->value()));
    expect($found->id)->toBe($evaluation->id()->value());

    $listed = (new ListAiProviderEvaluationsHandler($repository))->handle(new ListAiProviderEvaluationsQuery);
    expect($listed)->toHaveCount(2);

    expect(fn () => (new GetAiProviderEvaluationHandler($repository))->handle(new GetAiProviderEvaluationQuery((string) Str::uuid())))
        ->toThrow(AiProviderEvaluationNotFound::class);
});
