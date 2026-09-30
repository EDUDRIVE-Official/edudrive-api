<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\Admin\Domain\Repositories\SystemSettingRepository;
use Modules\Admin\Domain\ValueObjects\SystemSettingKey;
use Modules\Admin\Infrastructure\Jobs\ExportAuditLogsJob;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedAdminWebUser(): UserModel
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario sin permisos administrativos',
        email: Email::fromString(Str::uuid().'@edudrive.cr'),
        passwordHash: 'hash',
    );
    app(UserRepository::class)->save($user);

    return UserModel::query()->findOrFail($user->id());
}

it('requiere autenticación para el resumen administrativo', function (): void {
    /** @var TestCase $this */
    $this->get('/admin/sistema')->assertRedirect(route('login'));
});

it('muestra el resumen general con sus indicadores', function (): void {
    /** @var TestCase $this */
    $this->actingAs(actingAsSuperAdminUser(), 'web');
    createDraftCourseForPublishing('ADMIN-QA-01');

    $this->get('/admin/sistema')
        ->assertOk()
        ->assertSeeText('Resumen del sistema')
        ->assertSeeText('Usuarios')
        ->assertSeeText('Certificados')
        ->assertSeeText('Calidad pedagógica de las lecciones')
        ->assertSeeText('Lecciones revisadas')
        ->assertSeeText('Problemas más frecuentes')
        ->assertSeeText('ADMIN-QA-01');
});

it('muestra la salud y permite solicitar la exportación de auditoría', function (): void {
    /** @var TestCase $this */
    Queue::fake();
    $this->actingAs(actingAsSuperAdminUser(), 'web');

    $this->get('/admin/sistema/operaciones')
        ->assertOk()
        ->assertSeeText('Saludable')
        ->assertSeeText('Disponible')
        ->assertSeeText('Responsable');
    $this->post('/admin/sistema/auditoria/exportar')
        ->assertRedirect()
        ->assertSessionHas('status');

    Queue::assertPushed(ExportAuditLogsJob::class);
});

it('crea y actualiza una configuración desde la interfaz', function (): void {
    /** @var TestCase $this */
    $this->actingAs(actingAsSuperAdminUser(), 'web');

    $this->put('/admin/sistema/configuracion/maintenance_mode', ['value' => 'true'])
        ->assertRedirect()
        ->assertSessionHas('status');
    $this->get('/admin/sistema/configuracion')
        ->assertOk()
        ->assertSeeText('Modo de mantenimiento')
        ->assertSeeText('Activado')
        ->assertDontSeeText('Clave: maintenance_mode');

    expect(app(SystemSettingRepository::class)->findByKey(SystemSettingKey::fromString('maintenance_mode'))?->value())->toBe('true');
});

it('rechaza las pantallas del sistema a un usuario sin permisos', function (): void {
    /** @var TestCase $this */
    $this->actingAs(persistedAdminWebUser(), 'web');

    $this->get('/admin/sistema')->assertForbidden();
    $this->get('/admin/sistema/operaciones')->assertForbidden();
    $this->get('/admin/sistema/configuracion')->assertForbidden();
});
