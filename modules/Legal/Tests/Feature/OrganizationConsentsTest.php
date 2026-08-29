<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Organization\Domain\ValueObjects\OrganizationId;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedOrgConsentsMinor(string $organizationId): User
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante menor',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
        dateOfBirth: new DateTimeImmutable('-15 years'),
    );
    app(UserRepository::class)->save($user);

    $course = createDraftCourseForPublishing('CRT-'.strtoupper((string) Str::random(4)));

    app(EnrollmentRepository::class)->save(Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        userId: $user->id(),
        organizationId: OrganizationId::fromString($organizationId),
        source: EnrollmentSource::Institutional,
    ));

    return $user;
}

it('lista los estudiantes menores de una organizacion con el permiso organization_consents.view', function (): void {
    /** @var TestCase $this */
    $organizationId = (string) Str::uuid();
    $minor = persistedOrgConsentsMinor($organizationId);

    actingAsRole(Role::SuperAdmin);

    $this->getJson("/api/v1/legal/organizations/{$organizationId}/minors-consents")
        ->assertOk()
        ->assertJsonPath('data.0.user_id', $minor->id())
        ->assertJsonPath('data.0.consents', []);
});

it('rechaza consultar consentimientos de menores sin el permiso organization_consents.view', function (): void {
    /** @var TestCase $this */
    actingAsRole(Role::Teacher);

    $this->getJson('/api/v1/legal/organizations/'.Str::uuid().'/minors-consents')
        ->assertForbidden();
});

it('requiere autenticacion para consultar consentimientos de menores por organizacion', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/legal/organizations/'.Str::uuid().'/minors-consents')
        ->assertUnauthorized();
});
