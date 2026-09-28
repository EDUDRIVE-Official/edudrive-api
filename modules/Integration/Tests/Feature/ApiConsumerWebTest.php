<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Integration\Domain\Aggregates\ApiConsumer;
use Modules\Integration\Domain\Repositories\ApiConsumerRepository;
use Modules\Integration\Domain\ValueObjects\ApiConsumerId;
use Modules\Integration\Domain\ValueObjects\IntegrationKey;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedIntegrationWebUser(): UserModel
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario sin permisos de integración',
        email: Email::fromString(Str::uuid().'@edudrive.cr'),
        passwordHash: 'hash',
    );
    app(UserRepository::class)->save($user);

    return UserModel::query()->findOrFail($user->id());
}

function persistedIntegrationWebConsumer(): ApiConsumer
{
    $consumer = ApiConsumer::register(
        id: ApiConsumerId::fromString((string) Str::uuid()),
        name: 'Sistema institucional externo',
        scopes: ['reports.view'],
        integrationKey: IntegrationKey::generate(),
    );
    app(ApiConsumerRepository::class)->save($consumer);

    return $consumer;
}

it('requiere autenticación y permiso para consultar integraciones', function (): void {
    /** @var TestCase $this */
    $this->get('/admin/integraciones')->assertRedirect(route('login'));
    $this->actingAs(persistedIntegrationWebUser(), 'web');
    $this->get('/admin/integraciones')->assertForbidden();
});

it('registra una integración y muestra su llave una sola vez en sesión', function (): void {
    /** @var TestCase $this */
    $this->actingAs(actingAsSuperAdminUser(), 'web');

    $this->post('/admin/integraciones', [
        'name' => 'Plataforma del MEP',
        'scopes' => ['reports.view', 'certifications.view'],
    ])
        ->assertRedirect()
        ->assertSessionHas('status')
        ->assertSessionHas('integration_key', fn (mixed $key): bool => is_string($key) && strlen($key) === 64);

    expect(app(ApiConsumerRepository::class)->all())->toHaveCount(1);
});

it('lista y administra el ciclo de vida de una integración', function (): void {
    /** @var TestCase $this */
    $consumer = persistedIntegrationWebConsumer();
    $id = $consumer->id()->value();
    $this->actingAs(actingAsSuperAdminUser(), 'web');

    $this->get('/admin/integraciones')->assertOk()->assertSeeText('Sistema institucional externo');
    $this->post("/admin/integraciones/{$id}/suspender", ['reason' => 'Mantenimiento'])->assertRedirect()->assertSessionHas('status');
    expect(app(ApiConsumerRepository::class)->findById($consumer->id())?->status()->value)->toBe('suspended');
    $this->post("/admin/integraciones/{$id}/reactivar")->assertRedirect()->assertSessionHas('status');
    $this->post("/admin/integraciones/{$id}/rotar-llave")
        ->assertRedirect()
        ->assertSessionHas('integration_key', fn (mixed $key): bool => is_string($key) && strlen($key) === 64);
    $this->post("/admin/integraciones/{$id}/revocar", ['reason' => 'Finalizada'])->assertRedirect()->assertSessionHas('status');
    expect(app(ApiConsumerRepository::class)->findById($consumer->id())?->status()->value)->toBe('revoked');
});
