<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Enums\CourseModality;
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

it('redirige a un invitado que intenta ver la lista de cursos', function (): void {
    /** @var TestCase $this */
    $this->get('/courses')->assertRedirect(route('login'));
});

it('rechaza a un usuario autenticado sin ninguna asignación de rol', function (): void {
    /** @var TestCase $this */
    $user = actingAsAuthenticatedUser();
    $this->actingAs($user, 'web');

    $this->get('/courses')->assertForbidden();
});

it('muestra la lista con etiquetas legibles a un usuario con permiso de vista', function (): void {
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

    $courses = app(CourseRepository::class);
    $courses->save(Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('EDU-100'),
        title: CourseTitle::fromString('Curso Virtual de Prueba'),
        modality: CourseModality::Virtual,
        durationHours: 12,
    ));

    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $response = $this->get('/courses');

    $response->assertOk();
    $response->assertSeeText('Curso Virtual de Prueba');
    $response->assertSeeText('Virtual');
    $response->assertSeeText('Borrador');
});

it('muestra el botón "Nuevo curso" a un superadministrador', function (): void {
    /** @var TestCase $this */
    $user = actingAsSuperAdminUser();
    $this->actingAs($user, 'web');

    $response = $this->get('/courses');

    $response->assertOk();
    $response->assertSee('href="'.route('courses.create').'"', false);
});

it('muestra el boton segun el estado de revision de cada curso', function (): void {
    /** @var TestCase $this */
    $user = actingAsSuperAdminUser();
    $this->actingAs($user, 'web');

    $draft = createDraftCourseForPublishing('EDU-305');

    $underReview = createDraftCourseForPublishing('EDU-306');
    $this->post("/courses/{$underReview->id()->value()}/submit-for-review");

    $approved = createDraftCourseForPublishing('EDU-307');
    $this->post("/courses/{$approved->id()->value()}/submit-for-review");
    $this->post("/courses/{$approved->id()->value()}/approve");

    $published = createDraftCourseForPublishing('EDU-308');
    $this->post("/courses/{$published->id()->value()}/submit-for-review");
    $this->post("/courses/{$published->id()->value()}/approve");
    $this->post("/courses/{$published->id()->value()}/publish");

    $response = $this->get('/courses');

    $response->assertOk();
    $response->assertSee('action="'.route('courses.submitForReview', $draft->id()->value()).'"', false);
    $response->assertSee('action="'.route('courses.archive', $draft->id()->value()).'"', false);

    $response->assertSee('action="'.route('courses.approve', $underReview->id()->value()).'"', false);

    $response->assertSee('action="'.route('courses.publish', $approved->id()->value()).'"', false);

    $response->assertDontSee('action="'.route('courses.submitForReview', $published->id()->value()).'"', false);
    $response->assertDontSee('action="'.route('courses.approve', $published->id()->value()).'"', false);
    $response->assertDontSee('action="'.route('courses.publish', $published->id()->value()).'"', false);
});
