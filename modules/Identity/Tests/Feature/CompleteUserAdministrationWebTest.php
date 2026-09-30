<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\RoleAssignmentModel;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Organization\Infrastructure\Persistence\Eloquent\Models\OrganizationModel;
use Tests\TestCase;

function managedWebUser(string $name): User
{
    $user = User::register((string) Str::uuid(), $name, Email::fromString(Str::uuid().'@edudrive.cr'), Hash::make('InitialPassword123'));
    app(UserRepository::class)->save($user);

    return $user;
}

it('permite al superadministrador crear consultar y editar una cuenta', function (): void {
    /** @var TestCase $this */
    $this->actingAs(actingAsSuperAdminUser(), 'web');
    $email = Str::uuid().'@edudrive.cr';

    $response = $this->post(route('users.store'), [
        'name' => 'Nueva Estudiante',
        'email' => $email,
        'date_of_birth' => '2013-04-10',
        'password' => 'SafeInitial123',
        'password_confirmation' => 'SafeInitial123',
        'role' => 'student',
        'activate_now' => '1',
    ]);
    $created = UserModel::query()->where('email', $email)->firstOrFail();
    $response->assertRedirect(route('users.show', $created->id));
    expect($created->status)->toBe('active')
        ->and(RoleAssignmentModel::query()->where('user_id', $created->id)->where('role', 'student')->exists())->toBeTrue();

    $this->get(route('users.show', $created->id))->assertOk()->assertSeeText('Nueva Estudiante')->assertSeeText('Roles y organizaciones');
    $this->put(route('users.update', $created->id), [
        'name' => 'Estudiante Actualizada',
        'email' => $email,
        'date_of_birth' => '2012-04-10',
    ])->assertRedirect(route('users.show', $created->id));
    expect(UserModel::query()->findOrFail($created->id)->name)->toBe('Estudiante Actualizada');
});

it('genera una clave temporal y anonimiza sin borrar el expediente', function (): void {
    /** @var TestCase $this */
    $target = managedWebUser('Cuenta sensible');
    app(RoleAssignmentRepository::class)->save(RoleAssignment::assign((string) Str::uuid(), $target->id(), Role::Student, null));
    $this->actingAs(actingAsSuperAdminUser(), 'web');

    $response = $this->post(route('users.temporary-password', $target->id()));
    $response->assertRedirect(route('users.show', $target->id()))->assertSessionHas('temporary_password');
    $temporaryPassword = (string) session('temporary_password');
    expect(Hash::check($temporaryPassword, UserModel::query()->findOrFail($target->id())->password))->toBeTrue();

    $this->delete(route('users.anonymize', $target->id()))->assertRedirect(route('users.index'));
    $anonymized = UserModel::query()->findOrFail($target->id());
    expect($anonymized->name)->toBe('Usuario anonimizado')
        ->and($anonymized->email)->toStartWith('anon-')
        ->and($anonymized->date_of_birth)->toBeNull()
        ->and($anonymized->status)->toBe('inactive')
        ->and(RoleAssignmentModel::query()->where('user_id', $target->id())->exists())->toBeFalse()
        ->and(AuditLogModel::query()->where('action', 'identity.account_anonymized')->where('entity_id', $target->id())->exists())->toBeTrue();
});

it('limita al administrador institucional a usuarios de su organizacion', function (): void {
    /** @var TestCase $this */
    $organizationA = OrganizationModel::query()->create(['id' => (string) Str::uuid(), 'name' => 'Escuela A', 'type' => 'school']);
    $organizationB = OrganizationModel::query()->create(['id' => (string) Str::uuid(), 'name' => 'Escuela B', 'type' => 'school']);
    $admin = managedWebUser('Administradora A');
    $userA = managedWebUser('Estudiante Visible');
    $userB = managedWebUser('Estudiante Privado');
    app(RoleAssignmentRepository::class)->save(RoleAssignment::assign((string) Str::uuid(), $admin->id(), Role::InstitutionalAdmin, $organizationA->id));
    app(RoleAssignmentRepository::class)->save(RoleAssignment::assign((string) Str::uuid(), $userA->id(), Role::Student, $organizationA->id));
    app(RoleAssignmentRepository::class)->save(RoleAssignment::assign((string) Str::uuid(), $userB->id(), Role::Student, $organizationB->id));
    $this->actingAs(UserModel::query()->findOrFail($admin->id()), 'web');

    $this->get(route('users.index'))->assertOk()->assertSeeText('Estudiante Visible')->assertDontSeeText('Estudiante Privado');
    $this->get(route('users.show', $userB->id()))->assertNotFound();
    $this->post(route('users.store'), [
        'name' => 'Fuera de alcance', 'email' => Str::uuid().'@edudrive.cr',
        'password' => 'SafeInitial123', 'password_confirmation' => 'SafeInitial123',
        'role' => 'student', 'organization_id' => $organizationB->id,
    ])->assertForbidden();

    Sanctum::actingAs(UserModel::query()->findOrFail($admin->id()));
    $this->getJson('/api/v1/users')
        ->assertOk()
        ->assertJsonFragment(['id' => $userA->id()])
        ->assertJsonMissing(['id' => $userB->id()]);
    $this->getJson('/api/v1/users/'.$userB->id())->assertNotFound();
    $this->postJson('/api/v1/users/'.$userB->id().'/deactivate')->assertNotFound();
});
