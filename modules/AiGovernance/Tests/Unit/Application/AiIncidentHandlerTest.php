<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\AiGovernance\Application\Commands\ReportAiIncidentCommand;
use Modules\AiGovernance\Application\Commands\ResolveAiIncidentCommand;
use Modules\AiGovernance\Application\Commands\StartAiIncidentInvestigationCommand;
use Modules\AiGovernance\Application\Exceptions\AiIncidentNotFound;
use Modules\AiGovernance\Application\Exceptions\AiSystemNotFound;
use Modules\AiGovernance\Application\Exceptions\InvalidAiIncidentSeverity;
use Modules\AiGovernance\Application\Queries\GetAiIncidentQuery;
use Modules\AiGovernance\Application\Queries\ListAiIncidentsQuery;
use Modules\AiGovernance\Application\Responses\AiIncidentResponse;
use Modules\AiGovernance\Application\UseCases\GetAiIncidentHandler;
use Modules\AiGovernance\Application\UseCases\ListAiIncidentsHandler;
use Modules\AiGovernance\Application\UseCases\ReportAiIncidentHandler;
use Modules\AiGovernance\Application\UseCases\ResolveAiIncidentHandler;
use Modules\AiGovernance\Application\UseCases\StartAiIncidentInvestigationHandler;
use Modules\AiGovernance\Domain\Entities\AiIncident;
use Modules\AiGovernance\Domain\Enums\AiIncidentSeverity;
use Modules\AiGovernance\Domain\Exceptions\InvalidAiIncidentTransition;
use Modules\AiGovernance\Domain\Repositories\AiIncidentRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiSystemId;

final class InMemoryAiIncidentRepository implements AiIncidentRepository
{
    /** @var array<string, AiIncident> */
    public array $items = [];

    public function save(AiIncident $incident): void
    {
        $this->items[$incident->id()] = $incident;
    }

    public function findById(string $id): ?AiIncident
    {
        return $this->items[$id] ?? null;
    }

    /** @return list<AiIncident> */
    public function findByAiSystem(AiSystemId $aiSystemId): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (AiIncident $incident): bool => $incident->aiSystemId() === $aiSystemId->value(),
        ));
    }
}

it('reporta un incidente de IA para un sistema existente', function (): void {
    $incidents = new InMemoryAiIncidentRepository;
    $systems = new InMemoryAiSystemRepository;
    $system = persistedAiSystemFor($systems);

    $response = (new ReportAiIncidentHandler($systems, $incidents))->handle(new ReportAiIncidentCommand(
        aiSystemId: $system->id()->value(),
        severity: 'high',
        description: 'el modelo genero una respuesta sesgada',
    ));

    expect($response)->toBeInstanceOf(AiIncidentResponse::class)
        ->and($response->status)->toBe('open')
        ->and($response->severity)->toBe('high');
});

it('rechaza reportar un incidente con severidad invalida', function (): void {
    $incidents = new InMemoryAiIncidentRepository;
    $systems = new InMemoryAiSystemRepository;
    $system = persistedAiSystemFor($systems);

    expect(fn () => (new ReportAiIncidentHandler($systems, $incidents))->handle(new ReportAiIncidentCommand(
        aiSystemId: $system->id()->value(), severity: 'catastrofico', description: 'x',
    )))->toThrow(InvalidAiIncidentSeverity::class);
});

it('rechaza reportar un incidente para un sistema inexistente', function (): void {
    $incidents = new InMemoryAiIncidentRepository;
    $systems = new InMemoryAiSystemRepository;

    expect(fn () => (new ReportAiIncidentHandler($systems, $incidents))->handle(new ReportAiIncidentCommand(
        aiSystemId: (string) Str::uuid(), severity: 'low', description: 'x',
    )))->toThrow(AiSystemNotFound::class);
});

it('investiga y resuelve un incidente de IA existente', function (): void {
    $incidents = new InMemoryAiIncidentRepository;
    $systems = new InMemoryAiSystemRepository;
    $system = persistedAiSystemFor($systems);
    $incident = AiIncident::report((string) Str::uuid(), $system->id()->value(), AiIncidentSeverity::Medium, 'descripcion');
    $incidents->save($incident);

    $investigating = (new StartAiIncidentInvestigationHandler($incidents))->handle(new StartAiIncidentInvestigationCommand($incident->id()));
    expect($investigating->status)->toBe('investigating');

    $resolved = (new ResolveAiIncidentHandler($incidents))->handle(new ResolveAiIncidentCommand($incident->id(), 'se ajusto el prompt del modelo'));
    expect($resolved->status)->toBe('resolved')
        ->and($resolved->correctiveActions)->toBe('se ajusto el prompt del modelo');
});

it('rechaza mutar un incidente inexistente', function (): void {
    $incidents = new InMemoryAiIncidentRepository;
    $id = (string) Str::uuid();

    expect(fn () => (new StartAiIncidentInvestigationHandler($incidents))->handle(new StartAiIncidentInvestigationCommand($id)))
        ->toThrow(AiIncidentNotFound::class);
});

it('propaga el rechazo de dominio al resolver dos veces un incidente', function (): void {
    $incidents = new InMemoryAiIncidentRepository;
    $systems = new InMemoryAiSystemRepository;
    $system = persistedAiSystemFor($systems);
    $incident = AiIncident::report((string) Str::uuid(), $system->id()->value(), AiIncidentSeverity::Low, 'descripcion');
    $incidents->save($incident);
    (new ResolveAiIncidentHandler($incidents))->handle(new ResolveAiIncidentCommand($incident->id(), 'accion'));

    expect(fn () => (new ResolveAiIncidentHandler($incidents))->handle(new ResolveAiIncidentCommand($incident->id(), 'otra accion')))
        ->toThrow(InvalidAiIncidentTransition::class);
});

it('consulta y lista incidentes de IA por sistema', function (): void {
    $incidents = new InMemoryAiIncidentRepository;
    $systems = new InMemoryAiSystemRepository;
    $system = persistedAiSystemFor($systems);
    $incident = AiIncident::report((string) Str::uuid(), $system->id()->value(), AiIncidentSeverity::Low, 'descripcion');
    $incidents->save($incident);

    $found = (new GetAiIncidentHandler($incidents))->handle(new GetAiIncidentQuery($incident->id()));
    expect($found->id)->toBe($incident->id());

    $listed = (new ListAiIncidentsHandler($systems, $incidents))->handle(new ListAiIncidentsQuery($system->id()->value()));
    expect($listed)->toHaveCount(1);

    expect(fn () => (new ListAiIncidentsHandler($systems, $incidents))->handle(new ListAiIncidentsQuery((string) Str::uuid())))
        ->toThrow(AiSystemNotFound::class);
});
