<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Authorization\Domain\Enums\Role;
use Modules\RoadPassport\Domain\Aggregates\RoadPassport;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;
use Modules\RoadPassport\Domain\ValueObjects\RoadPassportId;

use function Pest\Laravel\assertDatabaseHas;

use Tests\TestCase;

it('permite a un estudiante inscribirse y comenzar un curso publicado', function (): void {
    /** @var TestCase $this */
    $course = createDraftCourseForPublishing('EDU-EXP-001');
    approveCourseForPublishing($course);
    app(CourseRepository::class)->save($course);
    $course->publish(new DateTimeImmutable, completeCoverageForCourse($course));
    app(CourseRepository::class)->save($course);

    $student = actingAsRole(Role::Student);
    $student->forceFill(['date_of_birth' => now()->subYears(8)->toDateString()])->save();
    $passport = RoadPassport::create(
        RoadPassportId::fromString((string) Str::uuid()),
        (string) $student->getAuthIdentifier(),
    );
    app(RoadPassportRepository::class)->save($passport);
    $this->actingAs($student, 'web');

    $this->get(route('courses.show', $course->id()->value()))
        ->assertOk()
        ->assertSeeText('Inscribirme');

    $response = $this->post(route('courses.enroll', $course->id()->value()));
    $response->assertRedirectContains('/my-courses/');

    assertDatabaseHas('academic_enrollments', [
        'course_id' => $course->id()->value(),
        'user_id' => (string) $student->getAuthIdentifier(),
        'status' => 'active',
        'source' => 'individual',
    ]);

    $learningUrl = $response->headers->get('Location');
    assert(is_string($learningUrl));

    $this->get($learningUrl)
        ->assertOk()
        ->assertSeeText('0% completado')
        ->assertSeeText('Escuchar lección')
        ->assertSeeText('Página 1 de')
        ->assertSee('changeLessonPage(lessonPage + 1)', false)
        ->assertSee('x-show="lessonPage === lastLessonPage"', false)
        ->assertSeeText('Apoyos, práctica y fuentes de esta lección')
        ->assertSeeText('Misión de movilidad segura')
        ->assertSeeText('Observá, decidí con margen y explicá cómo protegerías la vida.')
        ->assertSeeText('Escena en movimiento')
        ->assertSeeText('Pausar')
        ->assertSeeText('Ficha de práctica segura')
        ->assertSeeText('Imprimir ficha')
        ->assertSeeText('Lista de verificación')
        ->assertSeeText('nunca mientras cruzás, pedaleás o conducís')
        ->assertSeeText('¿Cómo te sentís para aplicar esta conducta?')
        ->assertSeeText('Podés elegir una idea con ayuda de una persona adulta')
        ->assertSeeText('Voy a pedir ayuda antes de acercarme a la vía.')
        ->assertSee('x-model="reflectionText"', false)
        ->assertSeeText('Verificar y completar lección');

    $enrollmentId = basename($learningUrl);
    $this->post(route('courses.entry-diagnostic.store', $enrollmentId), [
        'answers' => ['place' => 'view', 'change' => 'check', 'pressure' => 'route'],
    ])->assertRedirect(route('courses.learn', $enrollmentId).'#entry-diagnostic');
    $this->get(route('courses.learn', $enrollmentId))->assertSeeText('Tu punto de partida ya está guardado');
    $unitContent = app(UnitContentRepository::class)
        ->findForCourseUnit($course->id(), $course->modules()[0]->units()[0]->id());
    assert($unitContent !== null);
    $lessonId = $unitContent->lessons()[0]->id()->value();

    $this->post(route('courses.lessons.complete', [$enrollmentId, $lessonId]))
        ->assertSessionHasErrors(['reflection', 'self_assessment']);

    $this->post(route('courses.lessons.complete', [$enrollmentId, $lessonId]), [
        'reflection' => 'Me detendré y observaré antes de tomar una decisión.',
        'self_assessment' => 'necesito_practicar',
        'time_spent_minutes' => 7,
    ])
        ->assertRedirect(route('courses.learn', $enrollmentId).'#mission-complete');

    $this->get(route('courses.learn', $enrollmentId))
        ->assertOk()
        ->assertSeeText('100% completado')
        ->assertSeeText('Mi percepción del aprendizaje')
        ->assertSeeText('Quiero practicar más')
        ->assertSeeText('Refuerzo recomendado para vos')
        ->assertSeeText('Repasar esta lección');

    assertDatabaseHas('academic_enrollment_lesson_completions', [
        'enrollment_id' => $enrollmentId,
        'lesson_id' => $lessonId,
        'time_spent_minutes' => 7,
    ]);

    $this->post(route('courses.transfer-check.store', $enrollmentId), [
        'answers' => ['visibility' => 'marked', 'priority' => 'verify', 'inclusion' => 'route', 'selfcare' => 'pause'],
    ])->assertRedirect(route('courses.learn', $enrollmentId).'#transfer-check');
    $this->get(route('courses.learn', $enrollmentId))->assertSeeText('Esta transferencia ya forma parte de tu Pasaporte Vial');

    assertDatabaseHas('road_passport_evidence', [
        'road_passport_id' => $passport->id()->value(),
        'course_id' => $course->id()->value(),
        'type' => 'student_reflection',
    ]);
});

it('no permite inscribirse en un curso que sigue en borrador', function (): void {
    /** @var TestCase $this */
    $course = createDraftCourseForPublishing('SELF-ENROLL-02');
    $student = actingAsRole(Role::Student);
    $this->actingAs($student, 'web');

    $this->post(route('courses.enroll', $course->id()->value()))
        ->assertRedirect(route('courses.show', $course->id()->value()))
        ->assertSessionHas('error');
});

it('renderiza una decision practica dentro del contenido del curso', function (): void {
    /** @var TestCase $this */
    $html = view('courses.blocks.scenario', [
        'block' => [
            'id' => 'scenario-render-check',
            'payload' => [
                'title' => 'Decision bajo lluvia',
                'context' => 'La lluvia reduce la visibilidad de una bicicleta.',
                'prompt' => 'Que decision conserva margen?',
                'accessible_text' => 'Una bicicleta se aproxima bajo lluvia.',
                'choices' => [
                    ['id' => 'safe', 'label' => 'Reducir y aumentar distancia', 'feedback' => 'Correcto.', 'correct' => true],
                    ['id' => 'risk', 'label' => 'Continuar igual', 'feedback' => 'Falta adaptacion.', 'correct' => false],
                ],
            ],
        ],
    ])->render();

    expect($html)
        ->toContain('Decision bajo lluvia')
        ->toContain('Lluvia o superficie mojada')
        ->toContain('Reducir y aumentar distancia');
});

it('renderiza el diagnostico inicial del curso modelo', function (): void {
    /** @var TestCase $this */
    $html = view('courses.blocks.course-entry-diagnostic', [
        'learnerStage' => ['identity' => 'Aventurero Vial'],
        'enrollmentId' => '00000000-0000-4000-8000-000000000001',
    ])->render();

    expect($html)
        ->toContain('Descubrí cómo tomás decisiones hoy')
        ->toContain('Iniciar diagnóstico')
        ->toContain('Tu punto de partida')
        ->toContain('Aventurero Vial');
});

it('adapta el microreto del curso modelo a la etapa vital', function (string $stage, string $expected): void {
    $html = view('courses.blocks.age-adapted-mission', [
        'learnerStage' => ['stage' => $stage, 'identity' => 'Perfil de prueba'],
        'design' => ['behavior_objective' => 'Elegir un cruce con visibilidad.'],
        'lesson' => ['summary' => 'Practicar una decisión segura.'],
    ])->render();

    expect($html)->toContain($expected)->toContain('Elegir un cruce con visibilidad.');
})->with([
    ['explore', 'Buscá y señalá'],
    ['understand', 'Encontrá el riesgo oculto'],
    ['drive', 'Cambiá de perspectiva'],
    ['refresh', 'Ajustá el recorrido a tus condiciones'],
]);

it('renderiza la evaluacion final de transferencia del curso modelo', function (): void {
    $html = view('courses.blocks.safe-crossing-transfer-check', [
        'enrollmentId' => '00000000-0000-4000-8000-000000000001',
    ])->render();

    expect($html)
        ->toContain('Misión final: una salida que cambia')
        ->toContain('Percepción')
        ->toContain('Convivencia')
        ->toContain('Ver informe final');
});

it('adapta la rubrica de practica acompañada a la edad', function (string $stage, string $expected): void {
    $html = view('courses.blocks.guardian-observation-rubric', [
        'learnerStage' => ['stage' => $stage],
        'lesson' => ['title' => 'Cruzar con información', 'summary' => 'Elegir un lugar seguro.'],
        'design' => ['behavior_objective' => 'Confirmar todas las trayectorias.'],
    ])->render();

    expect($html)
        ->toContain('Rúbrica para la persona acompañante')
        ->toContain($expected)
        ->toContain('Mi acompañamiento');
})->with([
    ['explore', 'Señala personas, vehículos y al menos un peligro.'],
    ['perfect', 'Compara opciones usando tiempo, espacio y visibilidad.'],
]);
