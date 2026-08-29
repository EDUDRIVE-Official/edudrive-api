<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Application\Services\AuditLogger;
use Modules\Integration\Application\Commands\ReactivateApiConsumerCommand;
use Modules\Integration\Application\Commands\RegisterApiConsumerCommand;
use Modules\Integration\Application\Commands\RevokeApiConsumerCommand;
use Modules\Integration\Application\Commands\RotateApiConsumerIntegrationKeyCommand;
use Modules\Integration\Application\Commands\SuspendApiConsumerCommand;
use Modules\Integration\Application\Exceptions\ApiConsumerNotFound;
use Modules\Integration\Application\Exceptions\InvalidApiConsumerScope;
use Modules\Integration\Application\Queries\GetApiConsumerQuery;
use Modules\Integration\Application\Queries\ListApiConsumersQuery;
use Modules\Integration\Application\Responses\ApiConsumerResponse;
use Modules\Integration\Application\UseCases\GetApiConsumerHandler;
use Modules\Integration\Application\UseCases\ListApiConsumersHandler;
use Modules\Integration\Application\UseCases\ReactivateApiConsumerHandler;
use Modules\Integration\Application\UseCases\RegisterApiConsumerHandler;
use Modules\Integration\Application\UseCases\RevokeApiConsumerHandler;
use Modules\Integration\Application\UseCases\RotateApiConsumerIntegrationKeyHandler;
use Modules\Integration\Application\UseCases\SuspendApiConsumerHandler;
use Modules\Integration\Domain\Aggregates\ApiConsumer;
use Modules\Integration\Domain\Exceptions\InvalidApiConsumerTransition;
use Modules\Integration\Domain\Repositories\ApiConsumerRepository;
use Modules\Integration\Domain\ValueObjects\ApiConsumerId;
use Modules\Integration\Domain\ValueObjects\IntegrationKey;

final class InMemoryApiConsumerRepository implements ApiConsumerRepository
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
        return array_values($this->items);
    }
}

final class FakeAuditLoggerForApiConsumers implements AuditLogger
{
    /** @var list<AuditEntry> */
    public array $logged = [];

    public function log(AuditEntry $entry): void
    {
        $this->logged[] = $entry;
    }
}

function persistedApiConsumerFor(InMemoryApiConsumerRepository $repository): ApiConsumer
{
    $consumer = ApiConsumer::register(
        id: ApiConsumerId::fromString((string) Str::uuid()),
        name: 'Sistema externo de reportes',
        scopes: ['reports.view'],
        integrationKey: IntegrationKey::generate(),
    );
    $repository->save($consumer);

    return $consumer;
}

it('registra un consumidor de api nuevo con una llave de integracion en texto plano', function (): void {
    $repository = new InMemoryApiConsumerRepository;
    $auditLogger = new FakeAuditLoggerForApiConsumers;

    $response = (new RegisterApiConsumerHandler($repository, $auditLogger))->handle(new RegisterApiConsumerCommand(
        name: 'Sistema externo de reportes',
        scopes: ['reports.view'],
        expiresAt: null,
        actorId: 'actor-1',
    ));

    expect($response)->toBeInstanceOf(ApiConsumerResponse::class)
        ->and($response->name)->toBe('Sistema externo de reportes')
        ->and($response->scopes)->toBe(['reports.view'])
        ->and($response->status)->toBe('active')
        ->and($response->integrationKey)->not->toBeNull()
        ->and($response->integrationKey)->toMatch('/^[0-9a-f]{64}$/')
        ->and($auditLogger->logged)->toHaveCount(1)
        ->and($auditLogger->logged[0]->action)->toBe('integration.api_consumer_registered')
        ->and($auditLogger->logged[0]->userId)->toBe('actor-1');
});

it('rechaza registrar un consumidor con un alcance que no es un permiso valido', function (): void {
    $repository = new InMemoryApiConsumerRepository;
    $auditLogger = new FakeAuditLoggerForApiConsumers;

    expect(fn () => (new RegisterApiConsumerHandler($repository, $auditLogger))->handle(new RegisterApiConsumerCommand(
        name: 'Sistema externo',
        scopes: ['no.es.un.permiso'],
        expiresAt: null,
        actorId: 'actor-1',
    )))->toThrow(InvalidApiConsumerScope::class);

    expect($auditLogger->logged)->toBe([]);
});

it('rechaza registrar un consumidor con un permiso valido que no esta en la lista de alcances externos', function (): void {
    $repository = new InMemoryApiConsumerRepository;
    $auditLogger = new FakeAuditLoggerForApiConsumers;

    expect(fn () => (new RegisterApiConsumerHandler($repository, $auditLogger))->handle(new RegisterApiConsumerCommand(
        name: 'Sistema externo',
        scopes: ['system_settings.manage'],
        expiresAt: null,
        actorId: 'actor-1',
    )))->toThrow(InvalidApiConsumerScope::class);

    expect($auditLogger->logged)->toBe([]);
});

it('suspende, reactiva y revoca un consumidor existente auditando cada accion', function (): void {
    $repository = new InMemoryApiConsumerRepository;
    $auditLogger = new FakeAuditLoggerForApiConsumers;
    $consumer = persistedApiConsumerFor($repository);
    $id = $consumer->id()->value();

    $suspended = (new SuspendApiConsumerHandler($repository, $auditLogger))->handle(new SuspendApiConsumerCommand($id, 'Uso indebido', 'actor-1'));
    expect($suspended->status)->toBe('suspended');

    $reactivated = (new ReactivateApiConsumerHandler($repository, $auditLogger))->handle(new ReactivateApiConsumerCommand($id, 'actor-1'));
    expect($reactivated->status)->toBe('active');

    $revoked = (new RevokeApiConsumerHandler($repository, $auditLogger))->handle(new RevokeApiConsumerCommand($id, 'Integracion descontinuada', 'actor-1'));
    expect($revoked->status)->toBe('revoked');

    expect($auditLogger->logged)->toHaveCount(3)
        ->and($auditLogger->logged[0]->action)->toBe('integration.api_consumer_suspended')
        ->and($auditLogger->logged[1]->action)->toBe('integration.api_consumer_reactivated')
        ->and($auditLogger->logged[2]->action)->toBe('integration.api_consumer_revoked');
});

it('rechaza mutar un consumidor inexistente', function (): void {
    $repository = new InMemoryApiConsumerRepository;
    $auditLogger = new FakeAuditLoggerForApiConsumers;
    $id = (string) Str::uuid();

    expect(fn () => (new SuspendApiConsumerHandler($repository, $auditLogger))->handle(new SuspendApiConsumerCommand($id, null, 'actor-1')))
        ->toThrow(ApiConsumerNotFound::class);
    expect(fn () => (new ReactivateApiConsumerHandler($repository, $auditLogger))->handle(new ReactivateApiConsumerCommand($id, 'actor-1')))
        ->toThrow(ApiConsumerNotFound::class);
    expect(fn () => (new RevokeApiConsumerHandler($repository, $auditLogger))->handle(new RevokeApiConsumerCommand($id, null, 'actor-1')))
        ->toThrow(ApiConsumerNotFound::class);
});

it('propaga el rechazo de dominio ante una transicion invalida', function (): void {
    $repository = new InMemoryApiConsumerRepository;
    $auditLogger = new FakeAuditLoggerForApiConsumers;
    $consumer = persistedApiConsumerFor($repository);

    expect(fn () => (new ReactivateApiConsumerHandler($repository, $auditLogger))->handle(new ReactivateApiConsumerCommand($consumer->id()->value(), 'actor-1')))
        ->toThrow(InvalidApiConsumerTransition::class);
});

it('rota la llave de integracion, devuelve el nuevo valor en texto plano y audita la accion', function (): void {
    $repository = new InMemoryApiConsumerRepository;
    $auditLogger = new FakeAuditLoggerForApiConsumers;
    $consumer = persistedApiConsumerFor($repository);
    $originalHash = $consumer->integrationKey()->hash();

    $response = (new RotateApiConsumerIntegrationKeyHandler($repository, $auditLogger))->handle(new RotateApiConsumerIntegrationKeyCommand($consumer->id()->value(), 'actor-1'));

    expect($response->integrationKey)->not->toBeNull()
        ->and($response->integrationKey)->toMatch('/^[0-9a-f]{64}$/')
        ->and($repository->findById($consumer->id())?->integrationKey()->hash())->not->toBe($originalHash)
        ->and($auditLogger->logged)->toHaveCount(1)
        ->and($auditLogger->logged[0]->action)->toBe('integration.api_consumer_key_rotated');
});

it('rechaza rotar la llave de un consumidor inexistente', function (): void {
    $repository = new InMemoryApiConsumerRepository;
    $auditLogger = new FakeAuditLoggerForApiConsumers;

    expect(fn () => (new RotateApiConsumerIntegrationKeyHandler($repository, $auditLogger))->handle(new RotateApiConsumerIntegrationKeyCommand((string) Str::uuid(), 'actor-1')))
        ->toThrow(ApiConsumerNotFound::class);
});

it('consulta un consumidor por id sin exponer la llave de integracion', function (): void {
    $repository = new InMemoryApiConsumerRepository;
    $consumer = persistedApiConsumerFor($repository);

    $response = (new GetApiConsumerHandler($repository))->handle(new GetApiConsumerQuery($consumer->id()->value()));

    expect($response->id)->toBe($consumer->id()->value())
        ->and($response->integrationKey)->toBeNull();
});

it('rechaza consultar un consumidor inexistente', function (): void {
    $repository = new InMemoryApiConsumerRepository;

    expect(fn () => (new GetApiConsumerHandler($repository))->handle(new GetApiConsumerQuery((string) Str::uuid())))
        ->toThrow(ApiConsumerNotFound::class);
});

it('lista todos los consumidores registrados', function (): void {
    $repository = new InMemoryApiConsumerRepository;
    persistedApiConsumerFor($repository);
    persistedApiConsumerFor($repository);

    $responses = (new ListApiConsumersHandler($repository))->handle(new ListApiConsumersQuery);

    expect($responses)->toHaveCount(2)
        ->and($responses[0])->toBeInstanceOf(ApiConsumerResponse::class);
});
