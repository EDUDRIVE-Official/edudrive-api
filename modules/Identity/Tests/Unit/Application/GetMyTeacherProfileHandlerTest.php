<?php

declare(strict_types=1);

use Modules\Academic\Domain\Aggregates\Group;
use Modules\Academic\Domain\Repositories\GroupRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\GroupId;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Application\Queries\GetMyTeacherProfileQuery;
use Modules\Identity\Application\UseCases\GetMyTeacherProfileHandler;
use Modules\Identity\Domain\Entities\TeacherProfile;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Exceptions\UserNotFound;
use Modules\Identity\Domain\Repositories\TeacherProfileRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

final class InMemoryUserRepositoryForMyTeacherProfile implements UserRepository
{
    /** @var array<string, User> */
    public array $items = [];

    public function save(User $user): void
    {
        $this->items[$user->id()] = $user;
    }

    public function findById(string $id): ?User
    {
        return $this->items[$id] ?? null;
    }

    public function findByEmail(Email $email): ?User
    {
        return null;
    }

    public function existsByEmail(Email $email): bool
    {
        return false;
    }

    public function delete(string $id): void
    {
        unset($this->items[$id]);
    }

    /** @return list<User> */
    public function all(): array
    {
        return array_values($this->items);
    }

    /** @return list<User> */
    public function findInactiveBefore(DateTimeImmutable $threshold): array
    {
        return [];
    }
}

final class InMemoryTeacherProfileRepositoryForMyProfile implements TeacherProfileRepository
{
    private ?TeacherProfile $profile = null;

    public function withProfile(?TeacherProfile $profile): void
    {
        $this->profile = $profile;
    }

    public function save(TeacherProfile $profile): void
    {
        $this->profile = $profile;
    }

    public function findByUserId(string $userId): ?TeacherProfile
    {
        return $this->profile;
    }
}

final class InMemoryRoleAssignmentRepositoryForMyTeacherProfile implements RoleAssignmentRepository
{
    /** @var list<RoleAssignment> */
    private array $assignments = [];

    /** @param list<RoleAssignment> $assignments */
    public function withAssignments(array $assignments): void
    {
        $this->assignments = $assignments;
    }

    public function save(RoleAssignment $assignment): void {}

    /** @return list<RoleAssignment> */
    public function findByUserId(string $userId): array
    {
        return $this->assignments;
    }
}

final class InMemoryGroupRepositoryForMyTeacherProfile implements GroupRepository
{
    /** @var list<Group> */
    private array $groups = [];

    /** @param list<Group> $groups */
    public function withGroups(array $groups): void
    {
        $this->groups = $groups;
    }

    public function save(Group $group): void {}

    public function findById(GroupId $id): ?Group
    {
        return null;
    }

    public function all(?CourseId $courseId = null, ?string $teacherId = null): array
    {
        return $this->groups;
    }
}

function myTeacherProfileTestUser(): User
{
    return User::register(
        id: 'teacher-1',
        name: 'Ana Docente',
        email: Email::fromString('ana@edudrive.cr'),
        passwordHash: 'hashed-password',
    );
}

it('compone el perfil del docente con organizaciones, grupos y permisos de evaluacion', function (): void {
    $users = new InMemoryUserRepositoryForMyTeacherProfile;
    $users->save(myTeacherProfileTestUser());

    $profiles = new InMemoryTeacherProfileRepositoryForMyProfile;
    $profiles->withProfile(TeacherProfile::restore(
        userId: 'teacher-1',
        specialties: 'Manejo defensivo',
        certifications: 'Instructor certificado INA',
        updatedAt: new DateTimeImmutable('2026-08-30 10:00:00'),
    ));

    $roleAssignments = new InMemoryRoleAssignmentRepositoryForMyTeacherProfile;
    $roleAssignments->withAssignments([
        RoleAssignment::assign(id: 'assignment-1', userId: 'teacher-1', role: Role::Teacher, organizationId: 'org-1'),
        RoleAssignment::assign(id: 'assignment-2', userId: 'teacher-1', role: Role::Teacher, organizationId: 'org-2'),
        RoleAssignment::assign(id: 'assignment-3', userId: 'teacher-1', role: Role::Student, organizationId: 'org-3'),
    ]);

    $groups = new InMemoryGroupRepositoryForMyTeacherProfile;
    $groups->withGroups([
        Group::create(
            id: GroupId::fromString('01981a64-8300-7b1d-b442-764ea7f92111'),
            courseId: CourseId::fromString('01981a64-8300-7b1d-b442-764ea7f92112'),
            organizationId: 'org-1',
            name: 'Grupo A',
            teacherId: 'teacher-1',
            startsAt: new DateTimeImmutable('2026-01-15'),
            endsAt: new DateTimeImmutable('2026-06-15'),
        ),
    ]);

    $handler = new GetMyTeacherProfileHandler($users, $profiles, $roleAssignments, $groups);
    $response = $handler->handle(new GetMyTeacherProfileQuery(userId: 'teacher-1'));

    expect($response->name)->toBe('Ana Docente')
        ->and($response->specialties)->toBe('Manejo defensivo')
        ->and($response->certifications)->toBe('Instructor certificado INA')
        ->and($response->organizationIds)->toBe(['org-1', 'org-2'])
        ->and($response->groups)->toHaveCount(1)
        ->and($response->evaluationPermissions)->toContain('exams.view')
        ->and($response->evaluationPermissions)->toContain('exam_attempts.view')
        ->and($response->evaluationPermissions)->not->toContain('exams.manage');
});

it('devuelve listas vacias cuando el usuario no tiene asignaciones ni perfil', function (): void {
    $users = new InMemoryUserRepositoryForMyTeacherProfile;
    $users->save(myTeacherProfileTestUser());

    $handler = new GetMyTeacherProfileHandler(
        $users,
        new InMemoryTeacherProfileRepositoryForMyProfile,
        new InMemoryRoleAssignmentRepositoryForMyTeacherProfile,
        new InMemoryGroupRepositoryForMyTeacherProfile,
    );

    $response = $handler->handle(new GetMyTeacherProfileQuery(userId: 'teacher-1'));

    expect($response->specialties)->toBeNull()
        ->and($response->organizationIds)->toBeEmpty()
        ->and($response->groups)->toBeEmpty()
        ->and($response->evaluationPermissions)->toBeEmpty();
});

it('rechaza consultar el perfil de un usuario inexistente', function (): void {
    $handler = new GetMyTeacherProfileHandler(
        new InMemoryUserRepositoryForMyTeacherProfile,
        new InMemoryTeacherProfileRepositoryForMyProfile,
        new InMemoryRoleAssignmentRepositoryForMyTeacherProfile,
        new InMemoryGroupRepositoryForMyTeacherProfile,
    );

    $handler->handle(new GetMyTeacherProfileQuery(userId: 'no-existe'));
})->throws(UserNotFound::class);
