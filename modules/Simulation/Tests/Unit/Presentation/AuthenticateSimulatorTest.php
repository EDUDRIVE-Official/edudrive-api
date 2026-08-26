<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Simulation\Domain\Aggregates\Simulator;
use Modules\Simulation\Domain\Repositories\SimulatorRepository;
use Modules\Simulation\Domain\ValueObjects\DeviceIdentifier;
use Modules\Simulation\Domain\ValueObjects\IntegrationKey;
use Modules\Simulation\Domain\ValueObjects\SimulatorId;
use Modules\Simulation\Presentation\Http\Middleware\AuthenticateSimulator;

final class InMemorySimulatorRepositoryForAuth implements SimulatorRepository
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
        throw new LogicException('No usado en esta prueba.');
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
        throw new LogicException('No usado en esta prueba.');
    }
}

/** @return array{0: Simulator, 1: string} */
function registeredSimulatorWithKey(InMemorySimulatorRepositoryForAuth $repository): array
{
    $integrationKey = IntegrationKey::generate();
    $simulator = Simulator::register(
        id: SimulatorId::fromString((string) Str::uuid()),
        deviceIdentifier: DeviceIdentifier::fromString('SIM-'.strtoupper((string) Str::random(6))),
        softwareVersion: '1.0.0',
        location: null,
        integrationKey: $integrationKey,
    );
    $repository->save($simulator);

    return [$simulator, (string) $integrationKey->plainValue()];
}

it('autentica un simulador activo con una llave valida', function (): void {
    $repository = new InMemorySimulatorRepositoryForAuth;
    [$simulator, $plainKey] = registeredSimulatorWithKey($repository);
    $middleware = new AuthenticateSimulator($repository);

    $request = Request::create('/api/v1/simulation/sessions/x/telemetry', 'POST');
    $request->headers->set('Authorization', "Bearer {$plainKey}");

    $response = $middleware->handle($request, function (Request $req) use ($simulator) {
        expect($req->attributes->get('authenticated_simulator_id'))->toBe($simulator->id()->value());

        return response()->json(['ok' => true]);
    });

    expect($response->getStatusCode())->toBe(200);
});

it('rechaza una peticion sin llave', function (): void {
    $repository = new InMemorySimulatorRepositoryForAuth;
    $middleware = new AuthenticateSimulator($repository);

    $request = Request::create('/api/v1/simulation/sessions/x/telemetry', 'POST');
    $request->headers->set('Accept', 'application/json');

    $response = $middleware->handle($request, fn (Request $req) => response()->json(['ok' => true]));

    expect($response->getStatusCode())->toBe(401);
});

it('rechaza una llave que no corresponde a ningun simulador', function (): void {
    $repository = new InMemorySimulatorRepositoryForAuth;
    $middleware = new AuthenticateSimulator($repository);

    $request = Request::create('/api/v1/simulation/sessions/x/telemetry', 'POST');
    $request->headers->set('Authorization', 'Bearer llave-invalida');
    $request->headers->set('Accept', 'application/json');

    $response = $middleware->handle($request, fn (Request $req) => response()->json(['ok' => true]));

    expect($response->getStatusCode())->toBe(401);
});

it('rechaza la llave de un simulador que no esta activo', function (): void {
    $repository = new InMemorySimulatorRepositoryForAuth;
    [$simulator, $plainKey] = registeredSimulatorWithKey($repository);
    $simulator->suspend(null, new DateTimeImmutable('now'));
    $repository->save($simulator);
    $middleware = new AuthenticateSimulator($repository);

    $request = Request::create('/api/v1/simulation/sessions/x/telemetry', 'POST');
    $request->headers->set('Authorization', "Bearer {$plainKey}");
    $request->headers->set('Accept', 'application/json');

    $response = $middleware->handle($request, fn (Request $req) => response()->json(['ok' => true]));

    expect($response->getStatusCode())->toBe(401);
});
