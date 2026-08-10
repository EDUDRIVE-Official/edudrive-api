<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;

use function Pest\Laravel\postJson;

use Tests\TestCase;

it('archiva un curso en borrador', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $course = createDraftCourseForPublishing('EDU-030');

    $response = postJson("/api/v1/academic/courses/{$course->id()->value()}/archive");

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $course->id()->value())
        ->assertJsonPath('data.status', 'archived');

    $stored = app(CourseRepository::class)->findById($course->id());

    expect($stored?->status()->value)->toBe('archived')
        ->and($stored?->archivedAt())->not->toBeNull();
});

it('archiva un curso publicado', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $course = createDraftCourseForPublishing('EDU-031');
    approveCourseThroughReviewFlow($this, $course->id()->value());

    postJson("/api/v1/academic/courses/{$course->id()->value()}/publish")->assertOk();

    postJson("/api/v1/academic/courses/{$course->id()->value()}/archive")
        ->assertOk()
        ->assertJsonPath('data.status', 'archived');
});

it('rechaza archivar un curso que ya está archivado', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $course = createDraftCourseForPublishing('EDU-032');

    postJson("/api/v1/academic/courses/{$course->id()->value()}/archive")->assertOk();

    postJson("/api/v1/academic/courses/{$course->id()->value()}/archive")
        ->assertUnprocessable()
        ->assertJsonPath('code', 'COURSE_ALREADY_ARCHIVED');
});

it('rechaza archivar un curso inexistente', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    postJson('/api/v1/academic/courses/'.((string) Str::uuid()).'/archive')
        ->assertNotFound()
        ->assertJsonPath('code', 'COURSE_NOT_FOUND');
});

it('rechaza archivar sin el permiso courses.manage', function (): void {
    /** @var TestCase $this */
    $course = createDraftCourseForPublishing('EDU-033');

    $repository = app(UserRepository::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante',
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

    Sanctum::actingAs(UserModel::query()->findOrFail($user->id()));

    postJson("/api/v1/academic/courses/{$course->id()->value()}/archive")
        ->assertForbidden();
});
