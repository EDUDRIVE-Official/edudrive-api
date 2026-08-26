<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Simulation\Application\Commands\ReactivateSimulatorCommand;
use Modules\Simulation\Application\Commands\RegisterSimulatorCommand;
use Modules\Simulation\Application\Commands\RetireSimulatorCommand;
use Modules\Simulation\Application\Commands\RotateSimulatorIntegrationKeyCommand;
use Modules\Simulation\Application\Commands\SuspendSimulatorCommand;
use Modules\Simulation\Application\Exceptions\SimulatorAlreadyExists;
use Modules\Simulation\Application\Exceptions\SimulatorNotFound;
use Modules\Simulation\Application\Queries\GetSimulatorQuery;
use Modules\Simulation\Application\Queries\ListSimulatorsQuery;
use Modules\Simulation\Application\Responses\SimulatorResponse;
use Modules\Simulation\Application\UseCases\GetSimulatorHandler;
use Modules\Simulation\Application\UseCases\ListSimulatorsHandler;
use Modules\Simulation\Application\UseCases\ReactivateSimulatorHandler;
use Modules\Simulation\Application\UseCases\RegisterSimulatorHandler;
use Modules\Simulation\Application\UseCases\RetireSimulatorHandler;
use Modules\Simulation\Application\UseCases\RotateSimulatorIntegrationKeyHandler;
use Modules\Simulation\Application\UseCases\SuspendSimulatorHandler;
use Modules\Simulation\Domain\Aggregates\Simulator;
use Modules\Simulation\Domain\Exceptions\InvalidSimulatorTransition;
use Modules\Simulation\Domain\Repositories\SimulatorRepository;
use Modules\Simulation\Domain\ValueObjects\DeviceIdentifier;
use Modules\Simulation\Domain\ValueObjects\IntegrationKey;
use Modules\Simulation\Domain\ValueObjects\SimulatorId;

final class InMemorySimulatorRepository implements SimulatorRepository
{
    /** @var array<string, Simulator> */
    public array $items = [];

    public function save(Simulator $simulator): void
    {
        $this->items[$simulator->id()->value()] = $simulator;
    }

    public function findById(SimulatorId $id): ?Simulator
    {
        return $this->items[$id->value()] ?? null;
    }

    public function findByDeviceIdentifier(DeviceIdentifier $deviceIdentifier): ?Simulator
    {
        foreach ($this->items as $simulator) {
            if ($simulator->deviceIdentifier()->equals($deviceIdentifier)) {
                return $simulator;
            }
        }

        return null;
    }

    public function findByIntegrationKeyHash(string $integrationKeyHash): ?Simulator
    {
        foreach ($this->items as $simulator) {
            if ($simulator->integrationKey()->hash() === $integrationKeyHash) {
                return $simulator;
            }
        }

        return null;
    }

    /** @return list<Simulator> */
    public function all(): array
    {
        return array_values($this->items);
    }
}

function persistedSimulatorFor(InMemorySimulatorRepository $repository, ?string $deviceIdentifier = null): Simulator
{
    $simulator = Simulator::register(
        id: SimulatorId::fromString((string) Str::uuid()),
        deviceIdentifier: DeviceIdentifier::fromString($deviceIdentifier ?? 'SIM-'.strtoupper((string) Str::random(6))),
        softwareVersion: '1.0.0',
        location: null,
        integrationKey: IntegrationKey::generate(),
    );
    $repository->save($simulator);

    return $simulator;
}

it('registra un simulador nuevo con una llave de integracion en texto plano', function (): void {
    $repository = new InMemorySimulatorRepository;

    $response = (new RegisterSimulatorHandler($repository))->handle(new RegisterSimulatorCommand(
        deviceIdentifier: 'SIM-00042',
        softwareVersion: '2.3.1',
        location: 'Sede Cartago',
    ));

    expect($response)->toBeInstanceOf(SimulatorResponse::class)
        ->and($response->deviceIdentifier)->toBe('SIM-00042')
        ->and($response->softwareVersion)->toBe('2.3.1')
        ->and($response->location)->toBe('Sede Cartago')
        ->and($response->status)->toBe('active')
        ->and($response->integrationKey)->not->toBeNull()
        ->and($response->integrationKey)->toMatch('/^[0-9a-f]{64}$/');
});

it('rechaza registrar un segundo simulador con el mismo identificador de dispositivo', function (): void {
    $repository = new InMemorySimulatorRepository;
    persistedSimulatorFor($repository, 'SIM-00042');

    expect(fn () => (new RegisterSimulatorHandler($repository))->handle(new RegisterSimulatorCommand('SIM-00042', '1.0.0')))
        ->toThrow(SimulatorAlreadyExists::class);
});

it('suspende, reactiva y retira un simulador existente', function (): void {
    $repository = new InMemorySimulatorRepository;
    $simulator = persistedSimulatorFor($repository);
    $id = $simulator->id()->value();

    $suspended = (new SuspendSimulatorHandler($repository))->handle(new SuspendSimulatorCommand($id, 'Mantenimiento'));
    expect($suspended->status)->toBe('suspended');

    $reactivated = (new ReactivateSimulatorHandler($repository))->handle(new ReactivateSimulatorCommand($id));
    expect($reactivated->status)->toBe('active');

    $retired = (new RetireSimulatorHandler($repository))->handle(new RetireSimulatorCommand($id, 'Fin de vida util'));
    expect($retired->status)->toBe('retired');
});

it('rechaza mutar un simulador inexistente', function (): void {
    $repository = new InMemorySimulatorRepository;
    $id = (string) Str::uuid();

    expect(fn () => (new SuspendSimulatorHandler($repository))->handle(new SuspendSimulatorCommand($id)))
        ->toThrow(SimulatorNotFound::class);
    expect(fn () => (new ReactivateSimulatorHandler($repository))->handle(new ReactivateSimulatorCommand($id)))
        ->toThrow(SimulatorNotFound::class);
    expect(fn () => (new RetireSimulatorHandler($repository))->handle(new RetireSimulatorCommand($id)))
        ->toThrow(SimulatorNotFound::class);
});

it('propaga el rechazo de dominio ante una transicion invalida', function (): void {
    $repository = new InMemorySimulatorRepository;
    $simulator = persistedSimulatorFor($repository);

    expect(fn () => (new ReactivateSimulatorHandler($repository))->handle(new ReactivateSimulatorCommand($simulator->id()->value())))
        ->toThrow(InvalidSimulatorTransition::class);
});

it('rota la llave de integracion y devuelve el nuevo valor en texto plano', function (): void {
    $repository = new InMemorySimulatorRepository;
    $simulator = persistedSimulatorFor($repository);
    $originalHash = $simulator->integrationKey()->hash();

    $response = (new RotateSimulatorIntegrationKeyHandler($repository))->handle(new RotateSimulatorIntegrationKeyCommand($simulator->id()->value()));

    expect($response->integrationKey)->not->toBeNull()
        ->and($response->integrationKey)->toMatch('/^[0-9a-f]{64}$/')
        ->and($repository->findById($simulator->id())?->integrationKey()->hash())->not->toBe($originalHash);
});

it('rechaza rotar la llave de un simulador inexistente', function (): void {
    $repository = new InMemorySimulatorRepository;

    expect(fn () => (new RotateSimulatorIntegrationKeyHandler($repository))->handle(new RotateSimulatorIntegrationKeyCommand((string) Str::uuid())))
        ->toThrow(SimulatorNotFound::class);
});

it('consulta un simulador por id sin exponer la llave de integracion', function (): void {
    $repository = new InMemorySimulatorRepository;
    $simulator = persistedSimulatorFor($repository);

    $response = (new GetSimulatorHandler($repository))->handle(new GetSimulatorQuery($simulator->id()->value()));

    expect($response->id)->toBe($simulator->id()->value())
        ->and($response->integrationKey)->toBeNull();
});

it('rechaza consultar un simulador inexistente', function (): void {
    $repository = new InMemorySimulatorRepository;

    expect(fn () => (new GetSimulatorHandler($repository))->handle(new GetSimulatorQuery((string) Str::uuid())))
        ->toThrow(SimulatorNotFound::class);
});

it('lista todos los simuladores registrados', function (): void {
    $repository = new InMemorySimulatorRepository;
    persistedSimulatorFor($repository);
    persistedSimulatorFor($repository);

    $responses = (new ListSimulatorsHandler($repository))->handle(new ListSimulatorsQuery);

    expect($responses)->toHaveCount(2)
        ->and($responses[0])->toBeInstanceOf(SimulatorResponse::class);
});
