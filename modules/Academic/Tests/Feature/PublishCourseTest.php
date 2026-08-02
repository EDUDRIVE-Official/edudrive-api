<?php

declare(strict_types=1);

use DateTimeImmutable;
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

it('publica un curso en borrador', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $course = createDraftCourseForPublishing();

    $response = postJson("/api/v1/academic/courses/{$course->id()->value()}/publish");

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $course->id()->value())
        ->assertJsonPath('data.status', 'published');

    $stored = app(CourseRepository::class)->findById($course->id());

    expect($stored?->status()->value)->toBe('published')
        ->and($stored?->publishedAt())->not->toBeNull();
});

it('rechaza publicar un curso que ya está publicado', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $course = createDraftCourseForPublishing('EDU-021');

    postJson("/api/v1/academic/courses/{$course->id()->value()}/publish")->assertOk();

    postJson("/api/v1/academic/courses/{$course->id()->value()}/publish")
        ->assertUnprocessable()
        ->assertJsonPath('code', 'COURSE_ALREADY_PUBLISHED');
});

it('rechaza publicar un curso archivado', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $course = createDraftCourseForPublishing('EDU-023');
    $repository = app(CourseRepository::class);

    $course->archive(new DateTimeImmutable);
    $repository->save($course);

    postJson("/api/v1/academic/courses/{$course->id()->value()}/publish")
        ->assertUnprocessable()
        ->assertJsonPath('code', 'ARCHIVED_COURSE_CANNOT_BE_MODIFIED');
});

it('rechaza publicar un curso inexistente', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    postJson('/api/v1/academic/courses/'.((string) Str::uuid()).'/publish')
        ->assertNotFound()
        ->assertJsonPath('code', 'COURSE_NOT_FOUND');
});

it('rechaza publicar sin el permiso courses.manage', function (): void {
    /** @var TestCase $this */
    $course = createDraftCourseForPublishing('EDU-022');

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

    postJson("/api/v1/academic/courses/{$course->id()->value()}/publish")
        ->assertForbidden();
});
