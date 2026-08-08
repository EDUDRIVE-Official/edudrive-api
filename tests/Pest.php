<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\UnitContent;
use Modules\Academic\Domain\Entities\CourseModule;
use Modules\Academic\Domain\Entities\CourseUnit;
use Modules\Academic\Domain\Entities\Lesson;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\Services\ContentBlockFactory;
use Modules\Academic\Domain\ValueObjects\ContentBlockId;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseModuleId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;
use Modules\Academic\Domain\ValueObjects\CurriculumCode;
use Modules\Academic\Domain\ValueObjects\LessonId;
use Modules\Academic\Domain\ValueObjects\UnitContentCoverage;
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
    addMinimalCurriculum($course);

    $repository->save($course);
    addCompleteContentForCourse($course);

    return $course;
}

function addCompleteContentForCourse(Course $course): void
{
    $contents = app(UnitContentRepository::class);

    foreach ($course->modules() as $module) {
        foreach ($module->units() as $unit) {
            $contents->replaceAtomically(
                $course->id(),
                $unit->id(),
                UnitContent::create($unit->id(), [
                    Lesson::create(
                        LessonId::fromString((string) Str::uuid()),
                        CurriculumCode::fromString('LEC-01'),
                        'Leccion de prueba',
                        null,
                        10,
                        1,
                        [ContentBlockFactory::create(
                            ContentBlockId::fromString((string) Str::uuid()),
                            'text',
                            1,
                            ['markdown' => 'Contenido accesible de prueba.'],
                        )],
                    ),
                ]),
            );
        }
    }
}

function completeCoverageForCourse(Course $course): UnitContentCoverage
{
    $unitIds = [];
    foreach ($course->modules() as $module) {
        foreach ($module->units() as $unit) {
            $unitIds[] = $unit->id();
        }
    }

    return UnitContentCoverage::fromUnitIds($unitIds);
}

function addMinimalCurriculum(Course $course): void
{
    $course->replaceCurriculum([
        CourseModule::create(
            id: CourseModuleId::fromString((string) Str::uuid()),
            code: CurriculumCode::fromString('MOD-01'),
            title: 'Fundamentos',
            description: 'Modulo curricular minimo de prueba.',
            objectives: null,
            durationMinutes: 30,
            position: 1,
            prerequisiteModuleIds: [],
            units: [
                CourseUnit::create(
                    id: CourseUnitId::fromString((string) Str::uuid()),
                    code: CurriculumCode::fromString('UNI-01'),
                    title: 'Introduccion',
                    description: 'Unidad curricular minima de prueba.',
                    objectives: null,
                    durationMinutes: 15,
                    position: 1,
                    prerequisiteUnitIds: [],
                ),
            ],
        ),
    ]);
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
