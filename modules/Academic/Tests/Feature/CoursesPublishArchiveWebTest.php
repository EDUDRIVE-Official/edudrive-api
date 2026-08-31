<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

it('recorre el flujo completo de revision hasta publicar un curso', function (): void {
    /** @var TestCase $this */
    $user = actingAsSuperAdminUser();
    $this->actingAs($user, 'web');

    $course = createDraftCourseForPublishing('EDU-300');

    $this->post("/courses/{$course->id()->value()}/submit-for-review")
        ->assertRedirect(route('courses.index'))
        ->assertSessionHas('status');

    $this->post("/courses/{$course->id()->value()}/approve")
        ->assertRedirect(route('courses.index'))
        ->assertSessionHas('status');

    $this->post("/courses/{$course->id()->value()}/publish")
        ->assertRedirect(route('courses.index'))
        ->assertSessionHas('status');

    $stored = app(CourseRepository::class)->findById($course->id());
    expect($stored?->status()->value)->toBe('published');
});

it('archiva un curso en borrador directamente y redirige con un mensaje de éxito', function (): void {
    /** @var TestCase $this */
    $user = actingAsSuperAdminUser();
    $this->actingAs($user, 'web');

    $course = createDraftCourseForPublishing('EDU-301');

    $response = $this->post("/courses/{$course->id()->value()}/archive");

    $response->assertRedirect(route('courses.index'));
    $response->assertSessionHas('status');

    $stored = app(CourseRepository::class)->findById($course->id());
    expect($stored?->status()->value)->toBe('archived');
});

it('redirige con un mensaje de error al publicar un curso que aun no fue aprobado, sin romper con un 500', function (): void {
    /** @var TestCase $this */
    $user = actingAsSuperAdminUser();
    $this->actingAs($user, 'web');

    $course = createDraftCourseForPublishing('EDU-302');

    $response = $this->post("/courses/{$course->id()->value()}/publish");

    $response->assertRedirect(route('courses.index'));
    $response->assertSessionHas('error', 'El curso no se encuentra en el estado requerido para esta accion.');
});

it('redirige con un mensaje de error al archivar un curso ya archivado, sin romper con un 500', function (): void {
    /** @var TestCase $this */
    $user = actingAsSuperAdminUser();
    $this->actingAs($user, 'web');

    $course = createDraftCourseForPublishing('EDU-303');
    $this->post("/courses/{$course->id()->value()}/archive")->assertRedirect();

    $response = $this->post("/courses/{$course->id()->value()}/archive");

    $response->assertRedirect(route('courses.index'));
    $response->assertSessionHas('error', 'El curso ya está archivado.');
});

it('rechaza las acciones de ciclo de vida sin el permiso courses.manage', function (): void {
    /** @var TestCase $this */
    $course = createDraftCourseForPublishing('EDU-304');

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

    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->post("/courses/{$course->id()->value()}/submit-for-review")->assertForbidden();
    $this->post("/courses/{$course->id()->value()}/approve")->assertForbidden();
    $this->post("/courses/{$course->id()->value()}/publish")->assertForbidden();
    $this->post("/courses/{$course->id()->value()}/archive")->assertForbidden();
});
