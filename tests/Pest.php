<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

pest()
    ->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in(
        'Feature',
        '../modules/*/Tests/Unit',
        '../modules/*/Tests/Feature',
        '../modules/*/Tests/Integration',
    );

function actingAsAuthenticatedUser(): UserModel
{
    $repository = app(UserRepository::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario de prueba',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );

    $repository->save($user);

    $model = UserModel::query()->findOrFail($user->id());

    Sanctum::actingAs($model);

    return $model;
}

/**
 * Creates and persists a draft `Course`, for tests that need an existing
 * course to publish, archive, or otherwise act on. Shared by
 * `Modules\Academic\Tests\Feature\PublishCourseTest` and
 * `Modules\Academic\Tests\Feature\ArchiveCourseTest`.
 */
function createDraftCourseForPublishing(string $code = 'EDU-020'): Course
{
    $repository = app(CourseRepository::class);

    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString($code),
        title: CourseTitle::fromString('Curso de prueba'),
    );

    $repository->save($course);

    return $course;
}

/**
 * Like `actingAsAuthenticatedUser()`, but the acting user additionally holds
 * the `SuperAdmin` role, for endpoints gated behind a `permission:...`
 * middleware where `SuperAdmin` is the only role granted that permission
 * (per `RolePermissions`).
 */
function actingAsSuperAdminUser(): UserModel
{
    $repository = app(UserRepository::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario de prueba',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );

    $repository->save($user);

    $model = UserModel::query()->findOrFail($user->id());

    app(RoleAssignmentRepository::class)->save(
        RoleAssignment::assign(
            id: (string) Str::uuid(),
            userId: $user->id(),
            role: Role::SuperAdmin,
            organizationId: null,
        ),
    );

    Sanctum::actingAs($model);

    return $model;
}
