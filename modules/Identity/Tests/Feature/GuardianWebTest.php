<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\PublishCourseCommand;
use Modules\Academic\Application\UseCases\PublishCourseHandler;
use Modules\Academic\Domain\Aggregates\UnitContent;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\Entities\Lesson;
use Modules\Academic\Domain\ValueObjects\LessonLearningDesign;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Entities\GuardianRelationship;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\GuardianRelationshipRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Notification\Domain\Repositories\NotificationRepository;
use Modules\RoadPassport\Domain\Aggregates\RoadPassport;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;
use Modules\RoadPassport\Domain\ValueObjects\RoadPassportId;
use Tests\TestCase;

function guardianWebUser(string $name, bool $minor = false): User
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: $name,
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hash',
        dateOfBirth: new DateTimeImmutable($minor ? '2015-01-01' : '1985-01-01'),
    );
    app(UserRepository::class)->save($user);

    return $user;
}

function configureGuardianRequirementForCourseLesson(
    \Modules\Academic\Domain\Aggregates\Course $course,
    bool $required,
): void
{
    $unit = $course->modules()[0]->units()[0];
    $repository = app(UnitContentRepository::class);
    $content = $repository->findForCourseUnit($course->id(), $unit->id());
    assert($content !== null);
    $lesson = $content->lessons()[0];
    $design = LessonLearningDesign::fromArray([
        'stage' => 'prepare',
        'jurisdictions' => ['GLOBAL', 'CR'],
        'experience_type' => 'guided_practice',
        'behavior_objective' => 'Aplicar una decisión segura con acompañamiento.',
        'competency_id' => (string) Str::uuid(),
        'subcompetency_code' => 'FAMILIA.PRACTICA',
        'indicator_codes' => ['PRACTICA.SEGURA'],
        'evidence_rules' => [[
            'indicator_code' => 'PRACTICA.SEGURA',
            'event_type' => 'lesson_completed',
            'minimum_observations' => 1,
            'weight' => 1,
        ]],
        'requires_guardian' => $required,
        'normative_sources' => [[
            'url' => 'https://www.csv.go.cr/seguridad-vial-virtual1',
            'reviewed_at' => '2026-09-13',
        ]],
        'version' => 1,
    ]);

    $repository->replaceAtomically($course->id(), $unit->id(), UnitContent::create($unit->id(), [
        Lesson::create(
            $lesson->id(),
            $lesson->code(),
            $lesson->title(),
            $lesson->summary(),
            $lesson->durationMinutes(),
            $lesson->position(),
            $lesson->blocks(),
            $design,
        ),
    ]));
}

it('requiere autenticacion para consultar el acompañamiento', function (): void {
    /** @var TestCase $this */
    $this->get(route('guardians.web.index'))->assertRedirect(route('login'));
});

it('muestra solamente el progreso de una persona menor vinculada', function (): void {
    /** @var TestCase $this */
    $guardian = guardianWebUser('Persona Tutora');
    $minor = guardianWebUser('Estudiante Acompañado', true);
    app(GuardianRelationshipRepository::class)->save(GuardianRelationship::create(
        id: (string) Str::uuid(),
        guardianUserId: $guardian->id(),
        minorUserId: $minor->id(),
    ));
    $this->actingAs(UserModel::query()->findOrFail($guardian->id()), 'web');

    $this->get(route('guardians.web.index'))
        ->assertOk()
        ->assertSeeText('Estudiante Acompañado')
        ->assertSeeText('Al día');
    $this->get(route('guardians.web.show', $minor->id()))
        ->assertOk()
        ->assertSeeText('Progreso de Estudiante Acompañado')
        ->assertSeeText('Todavía no está inscrito en cursos');
});

it('impide consultar a una persona menor no vinculada', function (): void {
    /** @var TestCase $this */
    $guardian = guardianWebUser('Persona sin vínculo');
    $minor = guardianWebUser('Menor privado', true);
    $this->actingAs(UserModel::query()->findOrFail($guardian->id()), 'web');

    $this->get(route('guardians.web.show', $minor->id()))->assertNotFound();
});

it('registra una práctica acompañada en el pasaporte sin duplicarla', function (): void {
    /** @var TestCase $this */
    $course = createDraftCourseForPublishing('GUARDIAN-PRACTICE-01');
    configureGuardianRequirementForCourseLesson($course, true);
    approveCourseForPublishing($course);
    app(CourseRepository::class)->save($course);
    app(PublishCourseHandler::class)->handle(new PublishCourseCommand($course->id()->value()));

    $guardian = guardianWebUser('Persona Tutora de Práctica');
    $minor = guardianWebUser('Estudiante en Práctica', true);
    app(RoleAssignmentRepository::class)->save(RoleAssignment::assign(
        id: (string) Str::uuid(),
        userId: $minor->id(),
        role: Role::Student,
        organizationId: null,
    ));
    app(GuardianRelationshipRepository::class)->save(GuardianRelationship::create(
        id: (string) Str::uuid(),
        guardianUserId: $guardian->id(),
        minorUserId: $minor->id(),
    ));
    app(RoadPassportRepository::class)->save(RoadPassport::create(
        RoadPassportId::fromString((string) Str::uuid()),
        $minor->id(),
    ));

    $this->actingAs(UserModel::query()->findOrFail($minor->id()), 'web');
    $enrollmentResponse = $this->post(route('courses.enroll', $course->id()->value()));
    $enrollmentId = basename((string) $enrollmentResponse->headers->get('Location'));
    $content = app(UnitContentRepository::class)->findForCourseUnit(
        $course->id(),
        $course->modules()[0]->units()[0]->id(),
    );
    assert($content !== null);
    $lessonId = $content->lessons()[0]->id()->value();
    $this->post(route('courses.lessons.complete', [$enrollmentId, $lessonId]), [
        'reflection' => 'Voy a detenerme, observar y explicar antes de continuar.',
        'self_assessment' => 'puedo_aplicarlo',
    ]);

    $this->actingAs(UserModel::query()->findOrFail($guardian->id()), 'web');
    $this->get(route('guardians.web.index'))
        ->assertOk()
        ->assertSeeText('1 pendiente')
        ->assertSeeText('Atender prácticas');
    $this->get(route('guardians.web.show', $minor->id()).'?lesson_id='.$lessonId)
        ->assertOk()
        ->assertSeeText('1 pendiente')
        ->assertSeeText('Próxima sugerida')
        ->assertSeeText('Leccion de prueba')
        ->assertSee('id="practice-'.$lessonId.'"', false)
        ->assertSee('value="'.$lessonId.'" selected', false);
    $payload = [
        'enrollment_id' => $enrollmentId,
        'lesson_id' => $lessonId,
        'behavior' => 'made_safe_decision',
        'practice_context' => 'controlled_space',
        'note' => 'Se detuvo, observó y explicó por qué era seguro continuar.',
        'safe_environment' => '1',
    ];
    $this->post(route('guardians.web.observations.store', $minor->id()), $payload)
        ->assertRedirect(route('guardians.web.show', $minor->id()))
        ->assertSessionHas('success');

    $readyNotifications = array_values(array_filter(
        app(NotificationRepository::class)->allForUser($guardian->id()),
        static fn ($notification): bool => $notification->category() === 'practica_lista',
    ));
    expect($readyNotifications)->toHaveCount(1)
        ->and($readyNotifications[0]->status()->value)->toBe('read');

    $this->post(route('guardians.web.observations.store', $minor->id()), $payload);

    $passport = app(RoadPassportRepository::class)->findByUserId($minor->id());
    assert($passport !== null);
    $observations = array_filter(
        $passport->evidence(),
        static fn ($evidence): bool => $evidence->type->value === 'guided_practice_observed',
    );
    expect($observations)->toHaveCount(1);
    $observation = array_values($observations)[0];
    expect($observation->details)->not->toHaveKey('observer_user_id')
        ->and($observation->details['practice_context'])->toBe('controlled_space');
    $notifications = array_values(array_filter(
        app(NotificationRepository::class)->allForUser($minor->id()),
        static fn ($notification): bool => $notification->category() === 'acompañamiento',
    ));
    expect($notifications)->toHaveCount(1)
        ->and($notifications[0]->subject())->toContain('Nueva práctica acompañada')
        ->and($notifications[0]->actionUrl())->toBe('/mi-pasaporte-vial?observation='.$observation->subjectId.'#practice-reflection-'.$observation->subjectId);

    $this->actingAs(UserModel::query()->findOrFail($minor->id()), 'web');
    $this->get(route('road-passport.show').'?observation='.$observation->subjectId)
        ->assertOk()
        ->assertSee('id="practice-reflection-'.$observation->subjectId.'"', false)
        ->assertSee('data-selected-observation="true"', false);
    $this->post(route('road-passport.reflections.store'), [
        'observation_subject_id' => $observation->subjectId,
        'learned' => 'Aprendí a explicar el riesgo antes de tomar una decisión.',
        'next_action' => 'La próxima vez observaré con calma antes de continuar.',
    ])->assertSessionHas('status');
    $reflectionNotices = array_values(array_filter(
        app(NotificationRepository::class)->allForUser($guardian->id()),
        static fn ($notification): bool => $notification->category() === 'reflexion_acompañamiento',
    ));
    expect($reflectionNotices)->toHaveCount(1)
        ->and($reflectionNotices[0]->subject())->toContain('Estudiante en Práctica respondió');
    $this->actingAs(UserModel::query()->findOrFail($guardian->id()), 'web');

    $this->get(route('guardians.web.show', $minor->id()))
        ->assertOk()
        ->assertSeeText('Prácticas que acompañaste')
        ->assertSeeText('Tomó y explicó una decisión segura')
        ->assertSeeText('Espacio controlado sin tránsito')
        ->assertSeeText('Respuesta del estudiante')
        ->assertSeeText('Aprendí a explicar el riesgo antes de tomar una decisión.')
        ->assertSeeText('Bandeja de prácticas')
        ->assertSeeText('Prácticas registradas por vos')
        ->assertSeeText('Ya acompañaste todas las lecciones completadas hasta ahora');

    $individualCourse = createDraftCourseForPublishing('INDIVIDUAL-PRACTICE-01');
    configureGuardianRequirementForCourseLesson($individualCourse, false);
    approveCourseForPublishing($individualCourse);
    app(CourseRepository::class)->save($individualCourse);
    app(PublishCourseHandler::class)->handle(new PublishCourseCommand($individualCourse->id()->value()));

    $this->actingAs(UserModel::query()->findOrFail($minor->id()), 'web');
    $individualEnrollmentResponse = $this->post(route('courses.enroll', $individualCourse->id()->value()));
    $individualEnrollmentId = basename((string) $individualEnrollmentResponse->headers->get('Location'));
    $individualContent = app(UnitContentRepository::class)->findForCourseUnit(
        $individualCourse->id(),
        $individualCourse->modules()[0]->units()[0]->id(),
    );
    assert($individualContent !== null);
    $individualLessonId = $individualContent->lessons()[0]->id()->value();
    $this->post(route('courses.lessons.complete', [$individualEnrollmentId, $individualLessonId]), [
        'reflection' => 'Puedo aplicar esta actividad de manera individual.',
        'self_assessment' => 'puedo_aplicarlo',
    ]);

    $this->actingAs(UserModel::query()->findOrFail($guardian->id()), 'web');
    $this->post(route('guardians.web.observations.store', $minor->id()), array_merge($payload, [
        'enrollment_id' => $individualEnrollmentId,
        'lesson_id' => $individualLessonId,
    ]))
        ->assertNotFound();
});

it('rechaza observaciones sin confirmación de entorno seguro', function (): void {
    /** @var TestCase $this */
    $guardian = guardianWebUser('Persona Tutora Validación');
    $minor = guardianWebUser('Menor Validación', true);
    $this->actingAs(UserModel::query()->findOrFail($guardian->id()), 'web');

    $this->from(route('guardians.web.show', $minor->id()))
        ->post(route('guardians.web.observations.store', $minor->id()), [
            'enrollment_id' => (string) Str::uuid(),
            'lesson_id' => (string) Str::uuid(),
            'behavior' => 'identified_risk',
            'practice_context' => 'controlled_space',
            'note' => 'Observó un riesgo desde una zona protegida.',
        ])
        ->assertSessionHasErrors('safe_environment');
});

it('impide registrar prácticas para una persona menor no vinculada', function (): void {
    /** @var TestCase $this */
    $guardian = guardianWebUser('Persona Tutora sin Vínculo');
    $minor = guardianWebUser('Menor no Vinculado', true);
    $this->actingAs(UserModel::query()->findOrFail($guardian->id()), 'web');

    $this->post(route('guardians.web.observations.store', $minor->id()), [
        'enrollment_id' => (string) Str::uuid(),
        'lesson_id' => (string) Str::uuid(),
        'behavior' => 'identified_risk',
        'practice_context' => 'controlled_space',
        'note' => 'Observó un riesgo desde una zona protegida.',
        'safe_environment' => '1',
    ])->assertNotFound();
});
