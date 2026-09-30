<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;
use Tests\TestCase;

function persistedRoadPassportAdminTargetUser(): User
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante Objetivo',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user;
}

it('redirige a un invitado que intenta ver el buscador de pasaportes', function (): void {
    /** @var TestCase $this */
    $this->get('/admin/road-passport')->assertRedirect(route('login'));
});

it('rechaza a un usuario sin el permiso road_passports.view', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante Sin Permiso',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    $repository->save($user);

    app(RoleAssignmentRepository::class)->save(
        RoleAssignment::assign(
            id: (string) Str::uuid(),
            userId: $user->id(),
            role: Role::Student,
            organizationId: null,
        ),
    );

    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->get('/admin/road-passport')->assertForbidden();
});

it('muestra el formulario de emision cuando el usuario buscado no tiene pasaporte', function (): void {
    /** @var TestCase $this */
    $admin = actingAsSuperAdminUser();
    $this->actingAs($admin, 'web');

    $target = persistedRoadPassportAdminTargetUser();

    $response = $this->get('/admin/road-passport?user_id='.$target->id());

    $response->assertOk();
    $response->assertSeeText('no tiene un pasaporte vial emitido');
    $response->assertSee('action="'.route('road-passport.admin.issue').'"', false);
});

it('un docente puede ver un pasaporte ajeno pero no gestionarlo', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);

    $teacher = User::register(
        id: (string) Str::uuid(),
        name: 'Docente Vial',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    $repository->save($teacher);

    app(RoleAssignmentRepository::class)->save(
        RoleAssignment::assign(
            id: (string) Str::uuid(),
            userId: $teacher->id(),
            role: Role::Teacher,
            organizationId: null,
        ),
    );

    $admin = actingAsSuperAdminUser();
    $this->actingAs($admin, 'web');
    $target = persistedRoadPassportAdminTargetUser();
    $this->post('/admin/road-passport', ['user_id' => $target->id()])->assertRedirect();

    $this->actingAs(UserModel::query()->findOrFail($teacher->id()), 'web');

    $response = $this->get('/admin/road-passport?user_id='.$target->id());
    $response->assertOk();
    $response->assertSeeText('Activo');
    $response->assertDontSeeText('Suspender');
    $response->assertDontSeeText('Revocar');

    $this->post('/admin/road-passport', ['user_id' => $target->id()])->assertForbidden();
});

it('recorre el ciclo completo: emitir, cambiar nivel, suspender, reactivar y revocar', function (): void {
    /** @var TestCase $this */
    $admin = actingAsSuperAdminUser();
    $this->actingAs($admin, 'web');
    $target = persistedRoadPassportAdminTargetUser();

    $this->post('/admin/road-passport', ['user_id' => $target->id()])
        ->assertRedirect(route('road-passport.admin.search', ['user_id' => $target->id()]))
        ->assertSessionHas('status');

    $passport = app(RoadPassportRepository::class)->findByUserId($target->id());
    expect($passport)->not->toBeNull();
    $passportId = $passport->id()->value();

    $this->put("/admin/road-passport/{$passportId}/level", ['level' => 2, 'user_id' => $target->id()])
        ->assertRedirect()
        ->assertSessionHas('status');

    $this->post("/admin/road-passport/{$passportId}/suspend", ['user_id' => $target->id(), 'reason' => 'Revision de rutina'])
        ->assertRedirect()
        ->assertSessionHas('status');

    $this->post("/admin/road-passport/{$passportId}/reactivate", ['user_id' => $target->id()])
        ->assertRedirect()
        ->assertSessionHas('status');

    $this->post("/admin/road-passport/{$passportId}/revoke", ['user_id' => $target->id(), 'reason' => 'Baja definitiva'])
        ->assertRedirect()
        ->assertSessionHas('status');

    $stored = app(RoadPassportRepository::class)->findById($passport->id());
    expect($stored?->status()->value)->toBe('revoked')
        ->and($stored?->level())->toBe(2);
});

it('redirige con un mensaje de error al reactivar un pasaporte activo, sin romper con un 500', function (): void {
    /** @var TestCase $this */
    $admin = actingAsSuperAdminUser();
    $this->actingAs($admin, 'web');
    $target = persistedRoadPassportAdminTargetUser();

    $this->post('/admin/road-passport', ['user_id' => $target->id()]);
    $passport = app(RoadPassportRepository::class)->findByUserId($target->id());

    $response = $this->post("/admin/road-passport/{$passport->id()->value()}/reactivate", ['user_id' => $target->id()]);

    $response->assertRedirect(route('road-passport.admin.search', ['user_id' => $target->id()]));
    $response->assertSessionHas('error');
});

it('rechaza emitir y gestionar pasaportes sin el permiso road_passports.manage', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Sin Permiso Manage',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    $repository->save($user);

    app(RoleAssignmentRepository::class)->save(
        RoleAssignment::assign(
            id: (string) Str::uuid(),
            userId: $user->id(),
            role: Role::Teacher,
            organizationId: null,
        ),
    );

    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');
    $target = persistedRoadPassportAdminTargetUser();

    $this->post('/admin/road-passport', ['user_id' => $target->id()])->assertForbidden();
});
