<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\Group;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\GroupRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\GroupId;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Application\Services\PasswordHasher;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Tests\TestCase;

function registerTeacherProfileTestUser(): User
{
    $hasher = app(PasswordHasher::class);
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario docente',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: $hasher->hash('clave-valida-123'),
    );
    $user->activate(new DateTimeImmutable);
    app(UserRepository::class)->save($user);

    return $user;
}

it('consulta el propio perfil docente compuesto sin haberlo completado todavia', function (): void {
    /** @var TestCase $this */
    $user = registerTeacherProfileTestUser();
    $token = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ])->json('data.token.access_token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/auth/me/teacher-profile')
        ->assertOk()
        ->assertJsonPath('data.profile.name', $user->name())
        ->assertJsonPath('data.profile.specialties', null)
        ->assertJsonPath('data.profile.organization_ids', [])
        ->assertJsonPath('data.profile.groups', [])
        ->assertJsonPath('data.profile.evaluation_permissions', []);
});

it('actualiza el propio perfil docente y lo refleja al consultarlo de nuevo', function (): void {
    /** @var TestCase $this */
    $user = registerTeacherProfileTestUser();
    $token = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ])->json('data.token.access_token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/v1/auth/me/teacher-profile', [
            'specialties' => 'Manejo defensivo, motocicletas',
            'certifications' => 'Instructor certificado INA',
        ])
        ->assertOk()
        ->assertJsonPath('data.profile.specialties', 'Manejo defensivo, motocicletas');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/auth/me/teacher-profile')
        ->assertOk()
        ->assertJsonPath('data.profile.specialties', 'Manejo defensivo, motocicletas')
        ->assertJsonPath('data.profile.certifications', 'Instructor certificado INA');
});

it('compone organizaciones, grupos asignados y permisos de evaluacion cuando tiene rol docente', function (): void {
    /** @var TestCase $this */
    $user = registerTeacherProfileTestUser();
    $token = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ])->json('data.token.access_token');

    app(RoleAssignmentRepository::class)->save(RoleAssignment::assign(
        id: (string) Str::uuid(),
        userId: $user->id(),
        role: Role::Teacher,
        organizationId: 'org-1',
    ));

    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('COURSE-TP-'.strtoupper((string) Str::random(4))),
        title: CourseTitle::fromString('Curso de prueba para perfil docente'),
    );
    app(CourseRepository::class)->save($course);

    app(GroupRepository::class)->save(Group::create(
        id: GroupId::fromString((string) Str::uuid()),
        courseId: $course->id(),
        organizationId: 'org-1',
        name: 'Grupo A',
        teacherId: $user->id(),
        startsAt: new DateTimeImmutable('2026-01-15'),
        endsAt: new DateTimeImmutable('2026-06-15'),
    ));

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/auth/me/teacher-profile')
        ->assertOk();

    $response->assertJsonPath('data.profile.organization_ids', ['org-1'])
        ->assertJsonCount(1, 'data.profile.groups')
        ->assertJsonPath('data.profile.groups.0.name', 'Grupo A');

    expect($response->json('data.profile.evaluation_permissions'))->toContain('exams.view');
});

it('rechaza actualizar el perfil docente con un texto demasiado largo', function (): void {
    /** @var TestCase $this */
    $user = registerTeacherProfileTestUser();
    $token = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email()->value(),
        'password' => 'clave-valida-123',
    ])->json('data.token.access_token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/v1/auth/me/teacher-profile', [
            'specialties' => str_repeat('a', 1001),
        ])
        ->assertJsonValidationErrors('specialties');
});

it('requiere autenticacion para consultar o actualizar el perfil docente', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/auth/me/teacher-profile')->assertUnauthorized();
    $this->putJson('/api/v1/auth/me/teacher-profile', [])->assertUnauthorized();
});
