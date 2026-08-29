<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Integration\Domain\Aggregates\ApiConsumer;
use Modules\Integration\Domain\Repositories\ApiConsumerRepository;
use Modules\Integration\Domain\ValueObjects\ApiConsumerId;
use Modules\Integration\Domain\ValueObjects\IntegrationKey;
use Modules\Integration\Presentation\Http\Middleware\AuthenticateApiConsumer;

final class InMemoryApiConsumerRepositoryForAuth implements ApiConsumerRepository
{
    /** @var array<string, ApiConsumer> */
    public array $items = [];

    public function save(ApiConsumer $consumer): void
    {
        $this->items[$consumer->id()->value()] = $consumer;
    }

    public function findById(ApiConsumerId $id): ?ApiConsumer
    {
        return $this->items[$id->value()] ?? null;
    }

    public function findByIntegrationKeyHash(string $integrationKeyHash): ?ApiConsumer
    {
        foreach ($this->items as $consumer) {
            if ($consumer->integrationKey()->hash() === $integrationKeyHash) {
                return $consumer;
            }
        }

        return null;
    }

    /** @return list<ApiConsumer> */
    public function all(): array
    {
        throw new LogicException('No usado en esta prueba.');
    }
}

/** @return array{0: ApiConsumer, 1: string} */
function registeredApiConsumerWithKey(InMemoryApiConsumerRepositoryForAuth $repository, array $scopes = ['reports.view']): array
{
    $integrationKey = IntegrationKey::generate();
    $consumer = ApiConsumer::register(
        id: ApiConsumerId::fromString((string) Str::uuid()),
        name: 'Sistema externo de reportes',
        scopes: $scopes,
        integrationKey: $integrationKey,
    );
    $repository->save($consumer);

    return [$consumer, (string) $integrationKey->plainValue()];
}

it('autentica un consumidor activo con una llave valida y expone sus alcances', function (): void {
    $repository = new InMemoryApiConsumerRepositoryForAuth;
    [$consumer, $plainKey] = registeredApiConsumerWithKey($repository);
    $middleware = new AuthenticateApiConsumer($repository);

    $request = Request::create('/api/v1/external/status', 'GET');
    $request->headers->set('Authorization', "Bearer {$plainKey}");

    $response = $middleware->handle($request, function (Request $req) use ($consumer) {
        expect($req->attributes->get('authenticated_api_consumer_id'))->toBe($consumer->id()->value())
            ->and($req->attributes->get('authenticated_api_consumer_scopes'))->toBe(['reports.view']);

        return response()->json(['ok' => true]);
    });

    expect($response->getStatusCode())->toBe(200);
});

it('rechaza una peticion sin llave', function (): void {
    $repository = new InMemoryApiConsumerRepositoryForAuth;
    $middleware = new AuthenticateApiConsumer($repository);

    $request = Request::create('/api/v1/external/status', 'GET');
    $request->headers->set('Accept', 'application/json');

    $response = $middleware->handle($request, fn (Request $req) => response()->json(['ok' => true]));

    expect($response->getStatusCode())->toBe(401);
});

it('rechaza una llave que no corresponde a ningun consumidor', function (): void {
    $repository = new InMemoryApiConsumerRepositoryForAuth;
    $middleware = new AuthenticateApiConsumer($repository);

    $request = Request::create('/api/v1/external/status', 'GET');
    $request->headers->set('Authorization', 'Bearer llave-invalida');
    $request->headers->set('Accept', 'application/json');

    $response = $middleware->handle($request, fn (Request $req) => response()->json(['ok' => true]));

    expect($response->getStatusCode())->toBe(401);
});

it('rechaza la llave de un consumidor que no esta activo', function (): void {
    $repository = new InMemoryApiConsumerRepositoryForAuth;
    [$consumer, $plainKey] = registeredApiConsumerWithKey($repository);
    $consumer->suspend(null, new DateTimeImmutable('now'));
    $repository->save($consumer);
    $middleware = new AuthenticateApiConsumer($repository);

    $request = Request::create('/api/v1/external/status', 'GET');
    $request->headers->set('Authorization', "Bearer {$plainKey}");
    $request->headers->set('Accept', 'application/json');

    $response = $middleware->handle($request, fn (Request $req) => response()->json(['ok' => true]));

    expect($response->getStatusCode())->toBe(401);
});

it('rechaza la llave de un consumidor expirado', function (): void {
    $repository = new InMemoryApiConsumerRepositoryForAuth;
    $integrationKey = IntegrationKey::generate();
    $consumer = ApiConsumer::register(
        id: ApiConsumerId::fromString((string) Str::uuid()),
        name: 'Sistema externo',
        scopes: ['reports.view'],
        integrationKey: $integrationKey,
        expiresAt: new DateTimeImmutable('2020-01-01T00:00:00+00:00'),
    );
    $repository->save($consumer);
    $middleware = new AuthenticateApiConsumer($repository);

    $request = Request::create('/api/v1/external/status', 'GET');
    $request->headers->set('Authorization', 'Bearer '.$integrationKey->plainValue());
    $request->headers->set('Accept', 'application/json');

    $response = $middleware->handle($request, fn (Request $req) => response()->json(['ok' => true]));

    expect($response->getStatusCode())->toBe(401);
});
