<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\UnitContent;
use Modules\Academic\Domain\Entities\ContentBlocks\TextContentBlock;
use Modules\Academic\Domain\Entities\CourseModule;
use Modules\Academic\Domain\Entities\CourseUnit;
use Modules\Academic\Domain\Entities\Lesson;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\ValueObjects\ContentBlockId;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseModuleId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;
use Modules\Academic\Domain\ValueObjects\CurriculumCode;
use Modules\Academic\Domain\ValueObjects\LessonId;
use Modules\Academic\Infrastructure\Services\PilotDraftWorkspace;
use Modules\Academic\Infrastructure\Services\PilotInstrumentRuns;
use Modules\Academic\Infrastructure\Services\PilotReadiness;
use Modules\Academic\Infrastructure\Services\PilotVisualCandidates;
use Modules\Academic\Infrastructure\Services\PilotVisualReviewers;
use Modules\Academic\Infrastructure\Services\PilotVisualReviews;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** @return array{course_id: string, lesson_id: string} */
function createDraftLessonForVisualCandidate(): array
{
    $courseId = CourseId::fromString((string) Str::uuid());
    $unitId = CourseUnitId::fromString((string) Str::uuid());
    $lessonId = LessonId::fromString((string) Str::uuid());
    $course = Course::create($courseId, CourseCode::fromString('VIS-'.Str::upper(Str::random(8))), CourseTitle::fromString('Curso candidato visual'));
    $course->replaceCurriculum([
        CourseModule::create(CourseModuleId::fromString((string) Str::uuid()), CurriculumCode::fromString('MOD-VIS'), 'Módulo visual', 'Módulo de prueba.', null, 30, 1, [], [
            CourseUnit::create($unitId, CurriculumCode::fromString('UNI-VIS'), 'Unidad visual', 'Unidad de prueba.', null, 30, 1, []),
        ]),
    ]);
    app(CourseRepository::class)->save($course);
    $block = TextContentBlock::fromPayload(ContentBlockId::fromString((string) Str::uuid()), 1, ['markdown' => 'Contenido previo.', 'title' => 'Introducción']);
    $content = UnitContent::create($unitId, [Lesson::create($lessonId, CurriculumCode::fromString('LEC-VIS'), 'Lección visual', null, 10, 1, [$block])]);
    app(UnitContentRepository::class)->replaceAtomically($courseId, $unitId, $content);

    return ['course_id' => $courseId->value(), 'lesson_id' => $lessonId->value()];
}

function designatePilotVisualReviewer(UserModel $user, string $specialty): string
{
    $user->forceFill(['status' => 'active'])->save();
    app(PilotVisualReviewers::class)->save([
        'user_id' => $user->id,
        'specialty' => $specialty,
        'organization' => 'Organización de prueba',
        'qualification' => 'Designación sintética para prueba automatizada.',
        'evidence_reference' => 'TEST-REF-001',
        'designated_on' => '2026-09-17',
        'active' => true,
    ], $user->id);

    return app(PilotVisualReviewers::class)->activeForUser($user->id)['event_id'];
}

it('requires authorization for the rehearsal', function (): void {
    $this->get('/pilot-instruments')->assertRedirect(route('login'));
    $this->actingAs(actingAsRole(Role::Student), 'web')->get('/pilot-instruments')->assertForbidden();
});

it('renders the isolated visual practice only for internal managers', function (): void {
    $this->actingAs(actingAsRole(Role::Student), 'web')->get('/pilot-instruments/visual')->assertForbidden();
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web')->get('/pilot-instruments/visual')->assertOk()
        ->assertSee('Luna quiere llegar a la otra acera')->assertSee('Descripción sin imagen');
    expect(DB::table('academic_pilot_runs')->count())->toBe(0);
    app()->instance('env', 'production');
    $this->get('/pilot-instruments/visual')->assertNotFound();
});

it('renders the isolated van practice only for internal managers', function (): void {
    $this->actingAs(actingAsRole(Role::Student), 'web')->get('/pilot-instruments/van')->assertForbidden();
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web')->get('/pilot-instruments/van')->assertOk()
        ->assertSee('La van que bloquea la vista')->assertSee('Descripción sin imagen');
    expect(DB::table('academic_pilot_runs')->count())->toBe(0);
    app()->instance('env', 'production');
    $this->get('/pilot-instruments/van')->assertNotFound();
});

it('renders the isolated descent practice only for internal managers', function (): void {
    $this->actingAs(actingAsRole(Role::Student), 'web')->get('/pilot-instruments/descent')->assertForbidden();
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web')->get('/pilot-instruments/descent')->assertOk()
        ->assertSee('Cambió el lugar de descenso')->assertSee('Descripción sin imagen');
    expect(DB::table('academic_pilot_runs')->count())->toBe(0);
    app()->instance('env', 'production');
    $this->get('/pilot-instruments/descent')->assertNotFound();
});

it('renders the isolated barrier practice only for internal managers', function (): void {
    $this->actingAs(actingAsRole(Role::Student), 'web')->get('/pilot-instruments/barrier')->assertForbidden();
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web')->get('/pilot-instruments/barrier')->assertOk()
        ->assertSee('Ruta interrumpida')->assertSee('Descripción sin imagen');
    expect(DB::table('academic_pilot_runs')->count())->toBe(0);
    app()->instance('env', 'production');
    $this->get('/pilot-instruments/barrier')->assertNotFound();
});

it('renders the visual sequence without storing academic evidence', function (): void {
    $this->actingAs(actingAsRole(Role::Student), 'web')->get('/pilot-instruments/visual-sequence')->assertForbidden();
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web')->get('/pilot-instruments/visual-sequence')->assertOk()
        ->assertSeeInOrder(['1 · OBSERVO', '2 · COMPRUEBO', '3 · CAMBIO EL PLAN', '4 · COMUNICO'])
        ->assertSee(route('pilot-instruments.van'), false)
        ->assertSee(route('pilot-instruments.visual'), false)
        ->assertSee(route('pilot-instruments.barrier'), false)
        ->assertSee(route('pilot-instruments.descent'), false)
        ->assertSee('Las marcas de avance son temporales')
        ->assertSee('Completar este ensayo no es una calificación');
    expect(DB::table('academic_pilot_runs')->count())->toBe(0);
});

it('renders the human review register for visual scenes', function (): void {
    $this->actingAs(actingAsRole(Role::Student), 'web')->get('/pilot-instruments/visual-review')->assertForbidden();
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web')->get('/pilot-instruments/visual-review')->assertOk()
        ->assertSee('No constituye aprobación')
        ->assertSee(PilotVisualReviews::VERSION)
        ->assertSee('No tenés una designación activa')
        ->assertDontSee('Guardar revisión interna');
    expect(DB::table('academic_pilot_runs')->count())->toBe(0);
});

it('provides one complete specialist guide without granting approval', function (): void {
    $this->actingAs(actingAsRole(Role::Student), 'web')->get('/pilot-instruments/visual-review-guide')->assertForbidden();
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web')->get('/pilot-instruments/visual-review-guide')->assertOk()
        ->assertSee(PilotVisualReviews::VERSION)
        ->assertSeeInOrder(['Docencia', 'Seguridad vial', 'Accesibilidad'])
        ->assertSeeInOrder(['La van que bloquea la vista', 'El carro que gira', 'Ruta interrumpida', 'Cambió el lugar de descenso'])
        ->assertSee('12 dictámenes internos trazables')
        ->assertSee('no significa “aprobada”')
        ->assertSee(route('pilot-instruments.visual-review'), false);
});

it('requires role management permission to designate a visual reviewer', function (): void {
    $teacher = actingAsRole(Role::Teacher);
    $this->actingAs($teacher, 'web')->get('/pilot-instruments/visual-reviewers')->assertForbidden();
    $student = actingAsRole(Role::Student);
    $student->forceFill(['status' => 'active'])->save();

    $reviewer = actingAsRole(Role::SuperAdmin);
    $reviewer->forceFill(['status' => 'active'])->save();
    $this->actingAs($reviewer, 'web')->post('/pilot-instruments/visual-reviewers', [
        'user_id' => $reviewer->id,
        'specialty' => 'education',
        'organization' => 'Dirección académica de prueba',
        'qualification' => 'Responsable docente designado para el ensayo interno.',
        'evidence_reference' => 'OFICIO-P912-001',
        'designated_on' => '2026-09-17',
        'active' => '1',
    ])->assertRedirect(route('pilot-instruments.visual-reviewers'));

    $this->get('/pilot-instruments/visual-reviewers')->assertOk()
        ->assertSee($reviewer->email)->assertDontSee($student->email)
        ->assertSee('OFICIO-P912-001')->assertSee('No certifica credenciales profesionales');
    $this->get('/pilot-instruments/visual-review')->assertOk()
        ->assertSee('Designación activa:')->assertSee('Docencia')->assertSee('Guardar revisión interna')
        ->assertDontSee('Seleccioná una especialidad');
});

it('rejects a visual review from an undesignated manager', function (): void {
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web')->post('/pilot-instruments/visual-review', [
        'scene' => 'van', 'reviewed_on' => '2026-09-17',
        'criteria' => ['road_fidelity' => '1', 'decision_clarity' => '1', 'responsible_outcome' => '1', 'equivalent_access' => '1'],
        'findings' => '', 'status' => 'ready_for_specialist_review',
    ])->assertForbidden();
    expect(DB::table('academic_pilot_visual_reviews')->count())->toBe(0);
});

it('preserves designation history and links a review to its original designation', function (): void {
    $reviewer = actingAsRole(Role::SuperAdmin);
    $firstEvent = designatePilotVisualReviewer($reviewer, 'education');
    $this->actingAs($reviewer, 'web')->post('/pilot-instruments/visual-review', [
        'scene' => 'turn', 'reviewed_on' => '2026-09-17',
        'criteria' => ['road_fidelity' => '1', 'decision_clarity' => '1', 'responsible_outcome' => '1', 'equivalent_access' => '1'],
        'findings' => '', 'status' => 'ready_for_specialist_review',
    ])->assertRedirect();

    app(PilotVisualReviewers::class)->save([
        'user_id' => $reviewer->id,
        'specialty' => 'road_safety',
        'organization' => 'Organización actualizada',
        'qualification' => 'Nueva función declarada.',
        'evidence_reference' => 'TEST-REF-002',
        'designated_on' => '2026-09-18',
        'active' => true,
    ], $reviewer->id);
    $secondEvent = app(PilotVisualReviewers::class)->activeForUser($reviewer->id)['event_id'];

    expect($secondEvent)->not->toBe($firstEvent)
        ->and(DB::table('academic_pilot_visual_reviewer_events')->where('user_id', $reviewer->id)->count())->toBe(2)
        ->and(DB::table('academic_pilot_visual_reviews')->where('reviewer_user_id', $reviewer->id)->value('reviewer_designation_event_id'))->toBe($firstEvent);
    $this->get('/pilot-instruments/visual-reviewers')->assertOk()
        ->assertSee('Historial inalterable de designaciones (2)')
        ->assertSee('TEST-REF-001')->assertSee('TEST-REF-002');
});

it('stores an owner-scoped internal scene review without granting approval', function (): void {
    $reviewer = actingAsRole(Role::SuperAdmin);
    designatePilotVisualReviewer($reviewer, 'road_safety');
    $payload = [
        'scene' => 'van', 'specialty' => 'road_safety', 'reviewed_on' => '2026-09-17',
        'criteria' => ['road_fidelity' => '1', 'decision_clarity' => '1', 'responsible_outcome' => '1', 'equivalent_access' => '0'],
        'findings' => 'Comprobar con lector de pantalla.', 'status' => 'changes_required',
    ];
    $this->actingAs($reviewer, 'web')->post('/pilot-instruments/visual-review', $payload)->assertRedirect(route('pilot-instruments.visual-review'));
    expect(DB::table('academic_pilot_visual_reviews')->count())->toBe(1);
    $this->get('/pilot-instruments/visual-review')->assertOk()->assertSee('Comprobar con lector de pantalla.')->assertSee('Registro guardado');

    $other = actingAsRole(Role::SuperAdmin);
    $this->actingAs($other, 'web')->get('/pilot-instruments/visual-review')->assertOk()->assertDontSee('Comprobar con lector de pantalla.');
});

it('requires every criterion before specialist review readiness', function (): void {
    $payload = [
        'scene' => 'turn', 'specialty' => 'education', 'reviewed_on' => '2026-09-17',
        'criteria' => ['road_fidelity' => '1', 'decision_clarity' => '1', 'responsible_outcome' => '1', 'equivalent_access' => '0'],
        'findings' => '', 'status' => 'ready_for_specialist_review',
    ];
    $reviewer = actingAsRole(Role::SuperAdmin);
    designatePilotVisualReviewer($reviewer, 'education');
    $this->actingAs($reviewer, 'web')->post('/pilot-instruments/visual-review', $payload)->assertUnprocessable();
    expect(DB::table('academic_pilot_visual_reviews')->count())->toBe(0);
});

it('requires an actionable finding when a review requests changes', function (): void {
    $payload = [
        'scene' => 'barrier', 'specialty' => 'accessibility', 'reviewed_on' => '2026-09-17',
        'criteria' => ['road_fidelity' => '1', 'decision_clarity' => '1', 'responsible_outcome' => '1', 'equivalent_access' => '0'],
        'findings' => '', 'status' => 'changes_required',
    ];

    $reviewer = actingAsRole(Role::SuperAdmin);
    designatePilotVisualReviewer($reviewer, 'accessibility');
    $this->actingAs($reviewer, 'web')->post('/pilot-instruments/visual-review', $payload)
        ->assertSessionHasErrors('findings');
    expect(DB::table('academic_pilot_visual_reviews')->count())->toBe(0);
});

it('shows actionable blockers in the specialty coverage panel', function (): void {
    $reviewer = actingAsRole(Role::SuperAdmin);
    designatePilotVisualReviewer($reviewer, 'road_safety');
    $this->actingAs($reviewer, 'web')->post('/pilot-instruments/visual-review', [
        'scene' => 'descent', 'specialty' => 'road_safety', 'reviewed_on' => '2026-09-17',
        'criteria' => ['road_fidelity' => '0', 'decision_clarity' => '1', 'responsible_outcome' => '1', 'equivalent_access' => '1'],
        'findings' => 'Aumentar la distancia entre la puerta y el borde de circulación.', 'status' => 'changes_required',
    ])->assertRedirect();

    $coverage = app(PilotVisualReviews::class)->coverage();
    expect($coverage['descent']['has_changes'])->toBeTrue()
        ->and($coverage['descent']['blockers'])->toHaveCount(1)
        ->and($coverage['descent']['blockers'][0]['specialty'])->toBe('road_safety');
    $this->get('/pilot-instruments/visual-review-summary')->assertOk()
        ->assertSee('Cambios que bloquean la cobertura')
        ->assertSee('Aumentar la distancia entre la puerta y el borde de circulación.')
        ->assertSee(route('pilot-instruments.descent'), false);
});

it('summarizes independent specialty coverage without calling it approval', function (): void {
    $criteria = ['road_fidelity' => '1', 'decision_clarity' => '1', 'responsible_outcome' => '1', 'equivalent_access' => '1'];
    foreach (PilotVisualReviews::SPECIALTIES as $specialty) {
        $reviewer = actingAsRole(Role::SuperAdmin);
        designatePilotVisualReviewer($reviewer, $specialty);
        $this->actingAs($reviewer, 'web')->post('/pilot-instruments/visual-review', [
            'scene' => 'van', 'specialty' => $specialty, 'reviewed_on' => '2026-09-17',
            'criteria' => $criteria, 'findings' => '', 'status' => 'ready_for_specialist_review',
        ])->assertRedirect();
    }
    $coverage = app(PilotVisualReviews::class)->coverage();
    expect($coverage['van']['coverage_complete'])->toBeTrue()
        ->and($coverage['van']['reviews'])->toBe(3)
        ->and($coverage['turn']['missing_specialties'])->toBe(PilotVisualReviews::SPECIALTIES);
    $this->get('/pilot-instruments/visual-review-summary')->assertOk()
        ->assertSee('Cobertura completa')->assertSee('No concede aprobación');
});

it('does not allow an incompletely reviewed scene to become a lesson candidate', function (): void {
    $target = createDraftLessonForVisualCandidate();
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web')->post('/pilot-instruments/visual-candidates', [
        'scene' => 'turn', 'lesson_id' => $target['lesson_id'],
    ])->assertUnprocessable();

    expect(DB::table('academic_pilot_visual_candidates')->count())->toBe(0);
});

it('links a fully reviewed scene as a reversible candidate without changing lesson content', function (): void {
    $target = createDraftLessonForVisualCandidate();
    $criteria = ['road_fidelity' => true, 'decision_clarity' => true, 'responsible_outcome' => true, 'equivalent_access' => true];
    foreach (PilotVisualReviews::SPECIALTIES as $specialty) {
        $reviewer = actingAsRole(Role::SuperAdmin);
        $eventId = designatePilotVisualReviewer($reviewer, $specialty);
        app(PilotVisualReviews::class)->save($reviewer->id, [
            'scene' => 'van', 'specialty' => $specialty, 'reviewed_on' => '2026-09-17',
            'criteria' => $criteria, 'findings' => '', 'status' => 'ready_for_specialist_review',
        ], $eventId);
    }
    $blocksBefore = DB::table('academic_lesson_blocks')->where('lesson_id', $target['lesson_id'])->count();

    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web')->post('/pilot-instruments/visual-candidates', [
        'scene' => 'van', 'lesson_id' => $target['lesson_id'],
    ])->assertRedirect(route('pilot-instruments.visual-candidates'));

    expect(DB::table('academic_pilot_visual_candidates')->count())->toBe(1)
        ->and(DB::table('academic_lesson_blocks')->where('lesson_id', $target['lesson_id'])->count())->toBe($blocksBefore)
        ->and(DB::table('academic_courses')->where('id', $target['course_id'])->value('status'))->toBe('draft');
    $bindings = app(PilotVisualCandidates::class)->bindings();
    expect($bindings['van']['lesson_id'])->toBe($target['lesson_id']);
    $this->get('/pilot-instruments/visual-candidates')->assertOk()
        ->assertSee('Candidatura actual')->assertSee('Curso candidato visual')->assertSee('no alteró la lección');
});

it('creates one isolated P912 draft workspace without modifying published courses', function (): void {
    $publishedBefore = DB::table('academic_courses')->where('status', 'published')->pluck('status', 'id')->all();
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web')->post('/pilot-instruments/visual-candidates/create-draft')
        ->assertRedirect(route('pilot-instruments.visual-candidates'));

    $course = DB::table('academic_courses')->where('code', PilotDraftWorkspace::CODE)->first();
    assert($course !== null);
    expect($course)->not->toBeNull()
        ->and($course->status)->toBe('draft');
    $lessonCount = DB::table('academic_lessons as lesson')
        ->join('academic_course_units as unit', 'unit.id', '=', 'lesson.unit_id')
        ->join('academic_course_modules as module', 'module.id', '=', 'unit.module_id')
        ->where('module.course_id', $course->id)->count();
    expect($lessonCount)->toBe(4)
        ->and(DB::table('academic_courses')->where('status', 'published')->pluck('status', 'id')->all())->toBe($publishedBefore);

    $this->post('/pilot-instruments/visual-candidates/create-draft')->assertRedirect();
    expect(DB::table('academic_courses')->where('code', PilotDraftWorkspace::CODE)->count())->toBe(1)
        ->and(DB::table('academic_lessons as lesson')->join('academic_course_units as unit', 'unit.id', '=', 'lesson.unit_id')->join('academic_course_modules as module', 'module.id', '=', 'unit.module_id')->where('module.course_id', $course->id)->count())->toBe(4);
    $this->get('/pilot-instruments/visual-candidates')->assertOk()
        ->assertSee('Piloto Primaria 9–12: Peatones y pasajeros')
        ->assertSee(route('courses.show', $course->id), false)
        ->assertDontSee('Crear espacio borrador P912');
});

it('shows an honest readiness dashboard only to authorized internal users', function (): void {
    $this->actingAs(actingAsRole(Role::Student), 'web')->get('/pilot-instruments/visual-readiness')->assertForbidden();
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web')->get('/pilot-instruments/visual-readiness')->assertOk()
        ->assertSee('Preparación técnica incompleta')
        ->assertSee('0 de 4 controles')
        ->assertSee('Validación externa pendiente')
        ->assertSee('nunca debe convertirlos automáticamente en una aprobación final');
});

it('blocks participant authorization in the controlled pilot protocol while preparation is incomplete', function (): void {
    $this->actingAs(actingAsRole(Role::Student), 'web')->get('/pilot-instruments/pilot-protocol')->assertForbidden();
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web')->get('/pilot-instruments/pilot-protocol')->assertOk()
        ->assertSee('No autorizar participantes')
        ->assertSee('0 de 4 controles internos')
        ->assertSeeInOrder(['Fase 0 · Revisión documental', 'Fase 1 · Ensayo de mesa', 'Fase 2 · Accesibilidad y dispositivos', 'Fase 3 · Aplicación controlada', 'Fase 4 · Análisis y decisión'])
        ->assertSee('Criterios de suspensión inmediata')
        ->assertSee('no convierte ninguna casilla, revisión o resultado en autorización automática');
});

it('renders non-persistent operational instruments without personal student fields', function (): void {
    $this->actingAs(actingAsRole(Role::Student), 'web')->get('/pilot-instruments/pilot-forms')->assertForbidden();
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web')->get('/pilot-instruments/pilot-forms')->assertOk()
        ->assertSee('Plantillas sin almacenamiento')
        ->assertSee('Bloqueo automático: la preparación interna está incompleta')
        ->assertSeeInOrder(['Lista de control previa', 'Hoja de observación de sesión', 'Registro mínimo de incidente', 'Acta de análisis y decisión'])
        ->assertSee('No escribás datos personales de estudiantes')
        ->assertSee('Código de sesión')
        ->assertDontSee('Nombre del estudiante')
        ->assertDontSee('Guardar instrumentos')
        ->assertSee('EduDrive no los envía ni los guarda')
        ->assertSee(route('pilot-instruments.pilot-protocol'), false);
});

it('presents a traceable institutional dossier without overstating evidence', function (): void {
    $this->actingAs(actingAsRole(Role::Student), 'web')->get('/pilot-instruments/pilot-dossier')->assertForbidden();
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web')->get('/pilot-instruments/pilot-dossier')->assertOk()
        ->assertSee('Expediente preliminar')
        ->assertSee('Primaria 9–12')
        ->assertSee('0 de 4')
        ->assertSeeInOrder(['Diagnóstico', 'Práctica', 'Transferencia protegida', 'Comprobación', 'Seguimiento'])
        ->assertSee('No se solicita todavía aprobación nacional ni despliegue con estudiantes')
        ->assertSee('no acredita eficacia')
        ->assertSee('No contiene resultados de estudiantes')
        ->assertSee(route('pilot-instruments.pilot-protocol'), false)
        ->assertSee(route('pilot-instruments.pilot-forms'), false);
});

it('presents a sourced preliminary normative matrix without claiming approval', function (): void {
    $this->actingAs(actingAsRole(Role::Student), 'web')->get('/pilot-instruments/normative-alignment')->assertForbidden();
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web')->get('/pilot-instruments/normative-alignment')->assertOk()
        ->assertSee('Matriz normativa y curricular preliminar')
        ->assertSee('17 de septiembre de 2026')
        ->assertSee('Hasta el 19 de diciembre de 2026')
        ->assertSee('Desde el 20 de diciembre de 2026')
        ->assertSee('Determinación institucional pendiente')
        ->assertSee('Ley 9078 · artículo 217 vigente')
        ->assertSee('Leyes 8968 y 10238')
        ->assertSee('no constituye asesoría jurídica')
        ->assertSee('no debe presentarla como vigente antes de esa fecha')
        ->assertSee('Sistema Costarricense de Información Jurídica')
        ->assertSee('Ministerio de Educación Pública')
        ->assertSee(route('pilot-instruments.pilot-dossier'), false);

    $this->get('/pilot-instruments/pilot-dossier')->assertOk()
        ->assertSee('G. Matriz normativa y curricular')
        ->assertSee(route('pilot-instruments.normative-alignment'), false);
});

it('provides an unsent institutional review package without inventing approvals or contacts', function (): void {
    $this->actingAs(actingAsRole(Role::Student), 'web')->get('/pilot-instruments/institutional-review-package')->assertForbidden();
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web')->get('/pilot-instruments/institutional-review-package')->assertOk()
        ->assertSee('Paquete de solicitud de revisión institucional')
        ->assertSee('No ha sido enviado')
        ->assertSee('0 de 4 controles internos completos')
        ->assertSeeInOrder(['Modelo de oficio de presentación', 'Distribución sugerida por competencia', 'Instrucciones comunes para revisión', 'Formulario de observaciones institucionales'])
        ->assertSee('no solicita todavía aprobación nacional')
        ->assertSee('Esta página no envía ni almacena la información escrita')
        ->assertSee('EduDrive no envía este paquete')
        ->assertDontSee('Enviar solicitud')
        ->assertDontSee('Aprobado por el MEP')
        ->assertSee(route('pilot-instruments.normative-alignment'), false)
        ->assertSee(route('pilot-instruments.pilot-dossier'), false);

    $this->get('/pilot-instruments/pilot-dossier')->assertOk()
        ->assertSee('H. Paquete de revisión institucional')
        ->assertSee(route('pilot-instruments.institutional-review-package'), false);
});

it('consolidates the institutional submission without hiding missing annexes or controls', function (): void {
    $this->actingAs(actingAsRole(Role::Student), 'web')->get('/pilot-instruments/institutional-submission')->assertForbidden();
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web')->get('/pilot-instruments/institutional-submission')->assertOk()
        ->assertSee('Expediente consolidado para orientación curricular')
        ->assertSee('Borrador para revisión')
        ->assertSee('0 de 4 controles')
        ->assertSeeInOrder(['1. Control documental', '2. Resumen ejecutivo', '3. Resultados previstos', '4. Estado comprobable', '5. Síntesis normativa', '6. Registro de anexos', '7. Hoja de despacho'])
        ->assertSee('no incorpora automáticamente el contenido completo de los anexos A–I')
        ->assertSee('No se solicita todavía aprobación nacional')
        ->assertSee('No contiene resultados con estudiantes')
        ->assertSee('artículo 217')
        ->assertSee('8 fuentes oficiales')
        ->assertDontSee('Enviar expediente')
        ->assertSee(route('pilot-instruments.institutional-review-package'), false)
        ->assertSee(route('pilot-instruments.pilot-dossier'), false);

    $this->get('/pilot-instruments/pilot-dossier')->assertOk()
        ->assertSee('Versión consolidada')
        ->assertSee(route('pilot-instruments.institutional-submission'), false);
});

it('prepares reviewer onboarding without inventing people credentials or designations', function (): void {
    $this->actingAs(actingAsRole(Role::Student), 'web')->get('/pilot-instruments/reviewer-onboarding')->assertForbidden();
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web')->get('/pilot-instruments/reviewer-onboarding')->assertOk()
        ->assertSee('Incorporación de personas revisoras')
        ->assertSee('0 de 3 especialidades')
        ->assertSeeInOrder(['Docencia para primaria', 'Seguridad vial', 'Accesibilidad'])
        ->assertSee('Invitación a revisión técnica')
        ->assertSee('Declaración de alcance e independencia')
        ->assertSee('Acta mínima de incorporación')
        ->assertSee('no envía mensajes')
        ->assertSee('no puede certificar su autenticidad')
        ->assertDontSee('Invitación enviada')
        ->assertDontSee('Revisor aprobado')
        ->assertSee(route('pilot-instruments.visual-reviewers'), false)
        ->assertSee(route('pilot-instruments.institutional-submission'), false);

    $this->get('/pilot-instruments/institutional-submission')->assertOk()
        ->assertSee('Incorporación de personas revisoras')
        ->assertSee(route('pilot-instruments.reviewer-onboarding'), false);
});

it('gives a designated teacher only the isolated reviewer workflow', function (): void {
    $teacher = actingAsRole(Role::Teacher);
    $teacher->forceFill(['status' => 'active'])->save();
    $designatedBy = actingAsRole(Role::SuperAdmin);
    app(PilotVisualReviewers::class)->save([
        'user_id' => $teacher->id,
        'specialty' => 'education',
        'organization' => 'Centro verificado',
        'qualification' => 'Experiencia docente comprobada',
        'evidence_reference' => 'OF-TEST-01',
        'designated_on' => '2026-09-17',
        'active' => true,
    ], $designatedBy->id);

    $this->actingAs($teacher, 'web')->get('/pilot-instruments/visual-review')->assertOk()
        ->assertSee('Designación activa:')
        ->assertSee('Docencia')
        ->assertSee('OF-TEST-01');
    $this->get('/pilot-instruments/visual')->assertOk();
    $this->get('/pilot-instruments/visual-review-guide')->assertOk();
    $this->get('/pilot-instruments/visual-review-summary')->assertOk();
    $this->get('/pilot-instruments/visual-readiness')->assertForbidden();
    $this->get('/pilot-instruments/visual-reviewers')->assertForbidden();
    $this->get('/pilot-instruments/pilot-dossier')->assertForbidden();
});

it('coordinates real reviewer progress without exposing credentials or allowing reviewer administration', function (): void {
    config()->set('mail.delivery.mode', 'local');
    config()->set('mail.delivery.local_inbox_url', 'http://localhost:8025');
    $teacher = actingAsRole(Role::Teacher);
    $teacher->forceFill(['status' => 'active'])->save();
    $admin = actingAsRole(Role::SuperAdmin);
    app(PilotVisualReviewers::class)->save([
        'user_id' => $teacher->id,
        'specialty' => 'education',
        'organization' => 'Centro verificado',
        'qualification' => 'Experiencia comprobada',
        'evidence_reference' => 'OF-COORD-01',
        'designated_on' => '2026-09-18',
        'active' => true,
    ], $admin->id);

    $this->actingAs($admin, 'web')->get('/pilot-instruments/review-coordination')->assertOk()
        ->assertSee('Coordinación de revisiones especializadas')
        ->assertSee('1 de 3')
        ->assertSee('0 de 4')
        ->assertSee('OF-COORD-01')
        ->assertSee('Indicar el flujo de recuperación')
        ->assertSee('Las invitaciones todavía no salen a internet')
        ->assertSee('Transporte Postmark instalado')
        ->assertSee('Postmark seleccionado como proveedor')
        ->assertSee('http://localhost:8025', false)
        ->assertSee(route('users.show', $teacher->id), false)
        ->assertDontSee('Contraseña temporal:')
        ->assertDontSee('Completar revisión por la persona');

    $this->actingAs($teacher, 'web')->get('/pilot-instruments/review-coordination')->assertForbidden();
});

it('separates complete technical preparation from external approval', function (): void {
    $workspace = app(PilotDraftWorkspace::class)->ensure();
    $criteria = ['road_fidelity' => true, 'decision_clarity' => true, 'responsible_outcome' => true, 'equivalent_access' => true];

    foreach (PilotVisualReviews::SPECIALTIES as $specialty) {
        $reviewer = actingAsRole(Role::SuperAdmin);
        $eventId = designatePilotVisualReviewer($reviewer, $specialty);
        foreach (PilotVisualReviews::SCENES as $scene) {
            app(PilotVisualReviews::class)->save($reviewer->id, [
                'scene' => $scene,
                'specialty' => $specialty,
                'reviewed_on' => '2026-09-17',
                'criteria' => $criteria,
                'findings' => '',
                'status' => 'ready_for_specialist_review',
            ], $eventId);
        }
    }

    $lessonIds = DB::table('academic_lessons as lesson')
        ->join('academic_course_units as unit', 'unit.id', '=', 'lesson.unit_id')
        ->join('academic_course_modules as module', 'module.id', '=', 'unit.module_id')
        ->where('module.course_id', $workspace['course_id'])
        ->orderBy('lesson.position')
        ->pluck('lesson.id')
        ->all();
    $linkedBy = actingAsRole(Role::SuperAdmin);
    foreach (PilotVisualReviews::SCENES as $index => $scene) {
        app(PilotVisualCandidates::class)->assign($scene, $lessonIds[$index], $linkedBy->id);
    }

    $readiness = app(PilotReadiness::class)->status();
    expect($readiness['technical_preparation_complete'])->toBeTrue()
        ->and($readiness['workspace']['lessons'])->toBe(4)
        ->and($readiness['reviewers']['missing_specialties'])->toBe([])
        ->and($readiness['reviews']['completed_scenes'])->toBe(4)
        ->and($readiness['candidates']['linked_scenes'])->toBe(4);

    $this->actingAs($linkedBy, 'web')->get('/pilot-instruments/visual-readiness')->assertOk()
        ->assertSee('Preparación técnica completa')
        ->assertSee('4 de 4 controles')
        ->assertSee('Validación externa pendiente')
        ->assertSee('no autoriza publicar ni trabajar con estudiantes');
    $this->get('/pilot-instruments/pilot-protocol')->assertOk()
        ->assertSee('Puede solicitarse revisión externa')
        ->assertSee('4 de 4 controles internos')
        ->assertDontSee('No autorizar participantes');
    $this->get('/pilot-instruments/pilot-dossier')->assertOk()
        ->assertSee('4 de 4')
        ->assertSee('No equivale a aprobación');
});

it('runs the learner rehearsal in order without exposing future tasks', function (): void {
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web');
    $this->post('/pilot-instruments/journey/start', ['synthetic_only' => '1'])->assertRedirect();
    for ($position = 0; $position < 9; $position++) {
        $run = session('p912_journey');
        expect($run['position'])->toBe($position);
        $page = $this->get('/pilot-instruments/journey')->assertOk();
        if ($position === 0) {
            $page->assertSee('llegar al otro lado de la calle')->assertSee('aún no empezaste a cruzar');
            $page->assertDontSee('El giro desde otra calle')->assertDontSee('Necesito una pista');
        }
        if ($position === 2) {
            $page->assertSee('seguir caminando por la acera')->assertDontSee('aún no empezaste a cruzar');
        }
        if ($position === 3) {
            $page->assertDontSee('No ver un vehículo no significa que no venga');
            $this->post('/pilot-instruments/journey', ['token' => $run['token'], 'revision' => $run['revision'], 'action' => 'hint'])->assertRedirect();
            $run = session('p912_journey');
        }
        $this->post('/pilot-instruments/journey', ['token' => $run['token'], 'revision' => $run['revision'], 'action' => 'answer', 'response' => ''])->assertRedirect();
        $run = session('p912_journey');
        if ($position === 3) {
            expect($run['answers'][3][0]['hint_used'])->toBeTrue();
            $this->get('/pilot-instruments/journey')->assertSee('Pensemos en la decisión');
        }
        $this->post('/pilot-instruments/journey', ['token' => $run['token'], 'revision' => $run['revision'], 'action' => 'next'])->assertRedirect();
    }
    $this->get('/pilot-instruments/journey')->assertOk()->assertSee('Terminaste este recorrido de ensayo');
    expect(DB::table('academic_pilot_runs')->count())->toBe(0);
});

it('prevents skipping, hints in diagnosis and stale journey submissions', function (): void {
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web');
    $this->post('/pilot-instruments/journey/start', [])->assertSessionHasErrors('synthetic_only');
    $this->post('/pilot-instruments/journey/start', ['synthetic_only' => '1'])->assertRedirect();
    $run = session('p912_journey');
    $payload = ['token' => $run['token'], 'revision' => 0];
    $this->post('/pilot-instruments/journey', $payload + ['action' => 'next'])->assertUnprocessable();
    $this->post('/pilot-instruments/journey', $payload + ['action' => 'hint'])->assertUnprocessable();
    $this->post('/pilot-instruments/journey', $payload + ['action' => 'answer', 'response' => 'Prueba'])->assertRedirect();
    $this->post('/pilot-instruments/journey', $payload + ['action' => 'next'])->assertConflict();
    $this->post('/pilot-instruments/journey/start', ['synthetic_only' => '1'])->assertConflict();
});

it('keeps the learner rehearsal restricted to authorized internal users', function (): void {
    $this->actingAs(actingAsRole(Role::Student), 'web')->get('/pilot-instruments/journey')->assertForbidden();
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web');
    app()->instance('env', 'production');
    $this->get('/pilot-instruments/journey')->assertNotFound();
});

it('renders the unit guide without creating a run', function (): void {
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web');
    $this->get('/pilot-instruments/unit')->assertOk()
        ->assertSee('Me detengo, observo y decido antes de cruzar')
        ->assertSee('Tres prácticas guiadas')
        ->assertSee('Saltar al contenido del ensayo')
        ->assertSee('U01-A · Ayuda y explicación')
        ->assertSee('no son una instrucción de detenerse en medio del cruce')
        ->assertSee('no cambia ni filtra esas formas');
    expect(DB::table('academic_pilot_runs')->count())->toBe(0);
});

it('protects the teaching guide from students and production access', function (): void {
    $this->actingAs(actingAsRole(Role::Student), 'web')->get('/pilot-instruments/unit')->assertForbidden();
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web');
    app()->instance('env', 'production');
    $this->get('/pilot-instruments/unit')->assertNotFound();
});

it('requires confirmation of synthetic data before starting', function (): void {
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web');
    $this->post('/pilot-instruments', ['form' => 'diagnostic'])->assertSessionHasErrors('synthetic_only');
    expect(DB::table('academic_pilot_runs')->count())->toBe(0);
});

it('accepts a blank response and closure through the web flow', function (): void {
    $user = actingAsRole(Role::SuperAdmin);
    $this->actingAs($user, 'web');
    $this->post('/pilot-instruments', ['form' => 'diagnostic', 'synthetic_only' => '1'])->assertRedirect();
    $id = DB::table('academic_pilot_runs')->value('id');
    $this->post('/pilot-instruments/'.$id, ['revision' => 0, 'action' => 'response', 'item_id' => 'D01', 'response' => ''])->assertRedirect();
    $this->post('/pilot-instruments/'.$id, ['revision' => 1, 'action' => 'finish'])->assertRedirect();
    $this->get('/pilot-instruments/'.$id)->assertOk()->assertSee('Ensayo cerrado')->assertSee('D02, D03, D04');
});

it('escapes recorded responses when displaying the history', function (): void {
    $user = actingAsRole(Role::SuperAdmin);
    $this->actingAs($user, 'web');
    $runs = app(PilotInstrumentRuns::class);
    $id = $runs->start((string) $user->getAuthIdentifier(), 'diagnostic');
    $runs->append($id, (string) $user->getAuthIdentifier(), 0, 'response', ['item_id' => 'D01', 'response' => '<script>alert(1)</script>']);
    $this->get('/pilot-instruments/'.$id)->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
});

it('hides the rehearsal in production even from administrators', function (): void {
    $this->actingAs(actingAsRole(Role::SuperAdmin), 'web');
    app()->instance('env', 'production');
    $this->get('/pilot-instruments')->assertNotFound();
});

it('renders the internal runner without evaluator criteria', function (): void {
    $user = actingAsRole(Role::SuperAdmin);
    $this->actingAs($user, 'web');
    $id = app(PilotInstrumentRuns::class)->start((string) $user->getAuthIdentifier(), 'diagnostic');
    $this->get('/pilot-instruments/'.$id)->assertOk()->assertSee('La furgoneta junto al cruce')
        ->assertDontSee('Elige permanecer en un espacio de la acera separado del borde');
});

it('allows finishing without answering and without any enrollment', function (): void {
    $runs = app(PilotInstrumentRuns::class);
    $owner = (string) Str::uuid();
    $id = $runs->start($owner, 'diagnostic');
    $runs->append($id, $owner, 0, 'finish', []);
    $run = $runs->read($id, $owner);
    expect($run['status'])->toBe('closed')
        ->and($run['events'][0]['unanswered_items'])->toBe(['D01', 'D02', 'D03', 'D04'])
        ->and($run['events'][0]['demonstrates_mastery'])->toBeFalse();
    expect(DB::table('academic_enrollments')->count())->toBe(0);
});

it('preserves first response and content help across retries', function (): void {
    $runs = app(PilotInstrumentRuns::class);
    $owner = (string) Str::uuid();
    $id = $runs->start($owner, 'independent');
    $runs->append($id, $owner, 0, 'response', ['item_id' => 'C01', 'response' => 'Me acerco a la calle', 'content_help' => 'Buscá un espacio protegido']);
    $runs->append($id, $owner, 1, 'response', ['item_id' => 'C01', 'response' => 'Espero en la acera']);
    $run = $runs->read($id, $owner);
    expect($run['events'])->toHaveCount(2)
        ->and($run['events'][0]['response'])->toBe('Me acerco a la calle')
        ->and($run['events'][1]['response_context'])->toBe('formative');
});

it('does not expose hints or feedback before a practice response', function (): void {
    $runs = app(PilotInstrumentRuns::class);
    $owner = (string) Str::uuid();
    $id = $runs->start($owner, 'practice');
    $run = $runs->read($id, $owner);
    expect($run['items'][0])->not->toHaveKey('criteria')->not->toHaveKey('hints')->not->toHaveKey('feedback');
    $runs->append($id, $owner, 0, 'hint', ['item_id' => 'P01']);
    $runs->append($id, $owner, 1, 'response', ['item_id' => 'P01', 'response' => '']);
    $run = $runs->read($id, $owner);
    expect($run['items'][0])->toHaveKey('feedback');
    expect($run['events'][0]['type'])->toBe('hint')->and($run['events'][1]['response'])->toBe('');
});

it('rejects hints in independent forms', function (): void {
    $runs = app(PilotInstrumentRuns::class);
    $owner = (string) Str::uuid();
    $id = $runs->start($owner, 'diagnostic');
    $runs->append($id, $owner, 0, 'hint', ['item_id' => 'D01']);
})->throws(HttpException::class);

it('distinguishes a saved blank response from missing records', function (): void {
    $runs = app(PilotInstrumentRuns::class);
    $owner = (string) Str::uuid();
    $id = $runs->start($owner, 'diagnostic');
    $runs->append($id, $owner, 0, 'response', ['item_id' => 'D01', 'response' => '']);
    $runs->append($id, $owner, 1, 'finish', []);
    $events = $runs->read($id, $owner)['events'];
    expect($events[0]['response'])->toBe('')
        ->and($events[1]['unanswered_items'])->toBe(['D02', 'D03', 'D04']);
});

it('rejects access by another owner', function (): void {
    $runs = app(PilotInstrumentRuns::class);
    $id = $runs->start((string) Str::uuid(), 'diagnostic');
    $runs->read($id, (string) Str::uuid());
})->throws(HttpException::class);

it('rejects stale updates and changes to a closed run', function (): void {
    $runs = app(PilotInstrumentRuns::class);
    $owner = (string) Str::uuid();
    $id = $runs->start($owner, 'diagnostic');
    $runs->append($id, $owner, 0, 'response', ['item_id' => 'D01', 'response' => '']);
    try {
        $runs->append($id, $owner, 0, 'finish', []);
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(409);
    }
    expect($runs->read($id, $owner)['status'])->toBe('open');
    $runs->append($id, $owner, 1, 'finish', []);
    $runs->append($id, $owner, 2, 'response', ['item_id' => 'D02', 'response' => 'Algo']);
})->throws(HttpException::class);
