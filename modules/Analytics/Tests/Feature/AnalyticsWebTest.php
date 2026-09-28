<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\Analytics\Infrastructure\Jobs\GenerateAnalyticsReportJob;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedAnalyticsWebUser(): UserModel
{
    $user = User::register(id: (string) Str::uuid(), name: 'Usuario sin analítica', email: Email::fromString(Str::uuid().'@edudrive.cr'), passwordHash: 'hash');
    app(UserRepository::class)->save($user);

    return UserModel::query()->findOrFail($user->id());
}

it('requiere autenticación y permiso para abrir analítica', function (): void {
    /** @var TestCase $this */
    $this->get('/admin/analitica')->assertRedirect(route('login'));
    $this->actingAs(persistedAnalyticsWebUser(), 'web');
    $this->get('/admin/analitica')->assertForbidden();
});

it('muestra los tipos de reporte y permite solicitar uno', function (): void {
    /** @var TestCase $this */
    Queue::fake();
    $this->actingAs(actingAsSuperAdminUser(), 'web');
    $this->get('/admin/analitica')->assertOk()->assertSeeText('Matrículas')->assertSeeText('Certificados')->assertSeeText('Usuarios');

    $response = $this->post('/admin/analitica/reportes', ['type' => 'users_summary']);
    $response->assertRedirect()->assertSessionHas('status');
    $location = (string) $response->headers->get('Location');
    $jobId = basename($location);
    expect(Str::isUuid($jobId))->toBeTrue();
    Queue::assertPushed(GenerateAnalyticsReportJob::class);

    $this->get('/admin/analitica/reportes/'.$jobId)
        ->assertOk()
        ->assertSeeText('Resumen de usuarios')
        ->assertSeeText('Pendiente')
        ->assertDontSeeText($jobId);
    $this->get('/admin/analitica')
        ->assertOk()
        ->assertSeeText('Mis reportes recientes')
        ->assertSeeText('Resumen de usuarios');
});

it('impide consultar el reporte solicitado por otro usuario', function (): void {
    /** @var TestCase $this */
    Queue::fake();
    $this->actingAs(actingAsSuperAdminUser(), 'web');
    $response = $this->post('/admin/analitica/reportes', ['type' => 'enrollments_summary']);
    $jobId = basename((string) $response->headers->get('Location'));

    $this->actingAs(actingAsSuperAdminUser(), 'web');
    $this->get('/admin/analitica/reportes/'.$jobId)->assertNotFound();
});
