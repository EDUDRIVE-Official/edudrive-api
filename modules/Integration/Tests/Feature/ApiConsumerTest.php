<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Integration\Domain\Aggregates\ApiConsumer;
use Modules\Integration\Domain\Repositories\ApiConsumerRepository;
use Modules\Integration\Domain\ValueObjects\ApiConsumerId;
use Modules\Integration\Domain\ValueObjects\IntegrationKey;
use Tests\TestCase;

uses(RefreshDatabase::class);

/** @param list<string> $scopes; @return array{0: ApiConsumer, 1: string} */
function persistedApiConsumerFeature(array $scopes = ['reports.view']): array
{
    $integrationKey = IntegrationKey::generate();
    $consumer = ApiConsumer::register(
        id: ApiConsumerId::fromString((string) Str::uuid()),
        name: 'Sistema externo de reportes',
        scopes: $scopes,
        integrationKey: $integrationKey,
    );
    app(ApiConsumerRepository::class)->save($consumer);

    return [$consumer, (string) $integrationKey->plainValue()];
}

it('registra un consumidor con el permiso api_consumers.manage y devuelve la llave en texto plano', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/integration/api-consumers', [
        'name' => 'Sistema externo de reportes',
        'scopes' => ['reports.view'],
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Sistema externo de reportes')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.scopes', ['reports.view'])
        ->assertJson(fn ($json) => $json->where('data.integration_key', fn ($value) => is_string($value) && strlen($value) === 64)->etc());
});

it('rechaza registrar un consumidor sin el permiso api_consumers.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::InstitutionalAdmin);

    $this->postJson('/api/v1/integration/api-consumers', [
        'name' => 'Sistema externo',
        'scopes' => ['reports.view'],
    ])->assertForbidden();
});

it('rechaza registrar un consumidor con un alcance que no es un permiso valido', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);

    $this->postJson('/api/v1/integration/api-consumers', [
        'name' => 'Sistema externo',
        'scopes' => ['no.es.un.permiso'],
    ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_API_CONSUMER_SCOPE');
});

it('audita el registro de un consumidor con el id de quien lo realiza', function (): void {
    /** @var TestCase $this */
    $actor = actingAsSuperAdminUser();

    $this->postJson('/api/v1/integration/api-consumers', [
        'name' => 'Sistema externo de reportes',
        'scopes' => ['reports.view'],
    ])->assertCreated();

    $entry = AuditLogModel::query()->where('action', 'integration.api_consumer_registered')->latest('occurred_at')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->user_id)->toBe($actor->id);
});

it('lista los consumidores con el permiso api_consumers.view sin exponer la llave de integracion', function (): void {
    /** @var TestCase $this */
    [$consumer] = persistedApiConsumerFeature();
    actingAsRole(Role::SuperAdmin);

    $this->getJson('/api/v1/integration/api-consumers')
        ->assertOk()
        ->assertJsonPath('data.0.id', $consumer->id()->value())
        ->assertJsonMissingPath('data.0.integration_key');
});

it('consulta un consumidor por id sin exponer la llave de integracion', function (): void {
    /** @var TestCase $this */
    [$consumer] = persistedApiConsumerFeature();
    actingAsRole(Role::SuperAdmin);

    $this->getJson("/api/v1/integration/api-consumers/{$consumer->id()->value()}")
        ->assertOk()
        ->assertJsonPath('data.id', $consumer->id()->value())
        ->assertJsonMissingPath('data.integration_key');
});

it('rechaza listar y consultar consumidores sin el permiso api_consumers.view', function (): void {
    /** @var TestCase $this */
    [$consumer] = persistedApiConsumerFeature();
    actingAsRole(Role::InstitutionalAdmin);

    $this->getJson('/api/v1/integration/api-consumers')->assertForbidden();
    $this->getJson("/api/v1/integration/api-consumers/{$consumer->id()->value()}")->assertForbidden();
});

it('suspende, reactiva y revoca un consumidor con el permiso api_consumers.manage', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    [$consumer] = persistedApiConsumerFeature();
    $id = $consumer->id()->value();

    $this->postJson("/api/v1/integration/api-consumers/{$id}/suspend", ['reason' => 'Uso indebido'])
        ->assertOk()
        ->assertJsonPath('data.status', 'suspended');

    $this->postJson("/api/v1/integration/api-consumers/{$id}/reactivate")
        ->assertOk()
        ->assertJsonPath('data.status', 'active');

    $this->postJson("/api/v1/integration/api-consumers/{$id}/revoke", ['reason' => 'Integracion descontinuada'])
        ->assertOk()
        ->assertJsonPath('data.status', 'revoked');
});

it('rechaza mutar un consumidor sin el permiso api_consumers.manage', function (): void {
    /** @var TestCase $this */
    [$consumer] = persistedApiConsumerFeature();
    actingAsRole(Role::InstitutionalAdmin);
    $id = $consumer->id()->value();

    $this->postJson("/api/v1/integration/api-consumers/{$id}/suspend")->assertForbidden();
    $this->postJson("/api/v1/integration/api-consumers/{$id}/reactivate")->assertForbidden();
    $this->postJson("/api/v1/integration/api-consumers/{$id}/revoke")->assertForbidden();
    $this->postJson("/api/v1/integration/api-consumers/{$id}/rotate-key")->assertForbidden();
});

it('responde 422 ante una transicion invalida', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    [$consumer] = persistedApiConsumerFeature();

    $this->postJson("/api/v1/integration/api-consumers/{$consumer->id()->value()}/reactivate")
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_API_CONSUMER_TRANSITION');
});

it('rota la llave de integracion y devuelve el nuevo valor en texto plano', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::SuperAdmin);
    [$consumer] = persistedApiConsumerFeature();

    $this->postJson("/api/v1/integration/api-consumers/{$consumer->id()->value()}/rotate-key")
        ->assertOk()
        ->assertJson(fn ($json) => $json->where('data.integration_key', fn ($value) => is_string($value) && strlen($value) === 64)->etc());
});

it('requiere autenticacion para todos los endpoints protegidos', function (): void {
    /** @var TestCase $this */
    [$consumer] = persistedApiConsumerFeature();

    $this->getJson('/api/v1/integration/api-consumers')->assertUnauthorized();
    $this->getJson("/api/v1/integration/api-consumers/{$consumer->id()->value()}")->assertUnauthorized();
    $this->postJson('/api/v1/integration/api-consumers', ['name' => 'X', 'scopes' => ['reports.view']])->assertUnauthorized();
});

it('permite el acceso externo con una llave de integracion valida y el alcance requerido', function (): void {
    /** @var TestCase $this */
    [, $token] = persistedApiConsumerFeature(['reports.view']);

    $this->withToken($token)->getJson('/api/v1/external/status')->assertOk();
    $this->withToken($token)->getJson('/api/v1/external/reports/ping')
        ->assertOk()
        ->assertJsonPath('data.message', 'pong');
});

it('rechaza el acceso externo sin el alcance requerido', function (): void {
    /** @var TestCase $this */
    [, $token] = persistedApiConsumerFeature(['users.view']);

    $this->withToken($token)->getJson('/api/v1/external/reports/ping')->assertForbidden();
});

it('rechaza el acceso externo sin una llave de integracion valida', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/external/status')->assertUnauthorized();
    $this->withToken('llave-invalida')->getJson('/api/v1/external/status')->assertUnauthorized();
});

it('rechaza el acceso externo de un consumidor suspendido', function (): void {
    /** @var TestCase $this */
    [$consumer, $token] = persistedApiConsumerFeature();
    $consumer->suspend(null, new DateTimeImmutable('now'));
    app(ApiConsumerRepository::class)->save($consumer);

    $this->withToken($token)->getJson('/api/v1/external/status')->assertUnauthorized();
});
