<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\RoleAssignmentModel;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\OrganizationMembershipRequestModel;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Organization\Infrastructure\Persistence\Eloquent\Models\OrganizationModel;
use Tests\TestCase;

function membershipStudent(): UserModel
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante Solicitante',
        email: Email::fromString(Str::uuid().'@edudrive.cr'),
        passwordHash: 'hash',
    );
    app(UserRepository::class)->save($user);

    return UserModel::query()->findOrFail($user->id());
}

it('permite solicitar una organización y la vincula solo después de aprobación', function (): void {
    /** @var TestCase $this */
    $organization = OrganizationModel::query()->create([
        'id' => (string) Str::uuid(),
        'name' => 'Colegio Comunidad Segura',
        'type' => 'educational_center',
    ]);
    $student = membershipStudent();

    $this->actingAs($student, 'web')->get(route('student-profile.show'))
        ->assertOk()->assertSeeText('Colegio Comunidad Segura')->assertSeeText('Solicitar vinculación');

    $this->post(route('student-profile.organizations.request'), ['organization_id' => $organization->id])
        ->assertRedirect()->assertSessionHas('status');

    $membershipRequest = OrganizationMembershipRequestModel::query()->where('user_id', $student->id)->firstOrFail();
    expect($membershipRequest->status)->toBe('pending')
        ->and(RoleAssignmentModel::query()->where('user_id', $student->id)->where('organization_id', $organization->id)->exists())->toBeFalse();

    $admin = actingAsSuperAdminUser();
    $this->actingAs($admin, 'web')->get(route('organizations.index'))
        ->assertOk()->assertSeeText('Estudiante Solicitante')->assertSeeText('Colegio Comunidad Segura');
    $this->post(route('organizations.memberships.approve', $membershipRequest->id))
        ->assertRedirect()->assertSessionHas('status');

    expect($membershipRequest->fresh()?->status)->toBe('approved')
        ->and(RoleAssignmentModel::query()->where('user_id', $student->id)->where('organization_id', $organization->id)->where('role', Role::Student->value)->exists())->toBeTrue();

    $this->actingAs($student, 'web')->get(route('student-profile.show'))
        ->assertOk()->assertSeeText('Membresía activa')->assertSeeText('Colegio Comunidad Segura');
});

it('permite cancelar una solicitud propia y no la convierte en membresía', function (): void {
    /** @var TestCase $this */
    $organization = OrganizationModel::query()->create(['id' => (string) Str::uuid(), 'name' => 'Escuela Camino', 'type' => 'educational_center']);
    $student = membershipStudent();
    $this->actingAs($student, 'web')->post(route('student-profile.organizations.request'), ['organization_id' => $organization->id]);
    $membershipRequest = OrganizationMembershipRequestModel::query()->where('user_id', $student->id)->firstOrFail();

    $this->delete(route('student-profile.organizations.cancel', $membershipRequest->id))
        ->assertRedirect()->assertSessionHas('status');

    expect(OrganizationMembershipRequestModel::query()->whereKey($membershipRequest->id)->exists())->toBeFalse();
});
