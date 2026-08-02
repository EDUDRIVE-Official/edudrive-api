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
use Modules\Organization\Domain\Aggregates\Organization;
use Modules\Organization\Domain\Enums\OrganizationType;
use Modules\Organization\Domain\Repositories\OrganizationRepository;
use Modules\Organization\Domain\ValueObjects\OrganizationId;
use Modules\Organization\Domain\ValueObjects\OrganizationName;
use Tests\TestCase;

it('redirige a un invitado que intenta ver la lista de organizaciones', function (): void {
    /** @var TestCase $this */
    $this->get('/organizations')->assertRedirect(route('login'));
});

it('rechaza a un usuario autenticado sin ninguna asignación de rol', function (): void {
    /** @var TestCase $this */
    $user = actingAsAuthenticatedUser();
    $this->actingAs($user);

    $this->get('/organizations')->assertForbidden();
});

it('muestra la lista a un usuario con permiso de vista, sin el botón de crear', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Docente Web',
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

    $organizations = app(OrganizationRepository::class);
    $organizations->save(Organization::create(
        id: OrganizationId::fromString((string) Str::uuid()),
        name: OrganizationName::fromString('Centro Educativo EDUDRIVE'),
        type: OrganizationType::EducationalCenter,
    ));

    $this->actingAs(UserModel::query()->findOrFail($user->id()));

    $response = $this->get('/organizations');

    $response->assertOk();
    $response->assertSeeText('Centro Educativo EDUDRIVE');
    $response->assertDontSeeText('Nueva organización');
});

it('muestra el botón de crear a un superadministrador', function (): void {
    /** @var TestCase $this */
    $user = actingAsSuperAdminUser();
    $this->actingAs($user);

    $response = $this->get('/organizations');

    $response->assertOk();
    $response->assertSeeText('Nueva organización');
});
