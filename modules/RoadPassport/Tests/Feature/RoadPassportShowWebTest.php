<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Identity\Domain\Entities\GuardianRelationship;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\GuardianRelationshipRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\RoadPassport\Application\Services\RoadPassportVerificationCode;
use Modules\RoadPassport\Domain\Aggregates\RoadPassport;
use Modules\RoadPassport\Domain\Enums\EvidenceType;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;
use Modules\RoadPassport\Domain\ValueObjects\Evidence;
use Modules\RoadPassport\Domain\ValueObjects\RoadPassportId;
use Tests\TestCase;

function persistedRoadPassportWebTestUser(): User
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante Vial',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user;
}

it('redirige a un invitado que intenta ver su pasaporte vial', function (): void {
    /** @var TestCase $this */
    $this->get('/mi-pasaporte-vial')->assertRedirect(route('login'));
});

it('muestra un estado vacio cuando el usuario no tiene pasaporte emitido', function (): void {
    /** @var TestCase $this */
    $user = persistedRoadPassportWebTestUser();
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $response = $this->get('/mi-pasaporte-vial');

    $response->assertOk();
    $response->assertSeeText('Todavía no tenés un pasaporte vial');
});

it('muestra una evaluacion de transferencia como informe comprensible', function (): void {
    /** @var TestCase $this */
    $user = persistedRoadPassportWebTestUser();
    $course = createDraftCourseForPublishing('PASSPORT-TRANSFER-01');
    $passport = RoadPassport::create(RoadPassportId::fromString((string) Str::uuid()), $user->id());
    $passport->recordEvidence(Evidence::create(
        EvidenceType::StudentReflection,
        hash('sha256', 'entry-test'),
        $course->id()->value(),
        new DateTimeImmutable('-1 day'),
        ['lesson_title' => 'Diagnóstico inicial', 'evidence_kind' => 'course_entry_diagnostic', 'score' => 1, 'maximum_score' => 3, 'diagnostic_only' => true],
    ));
    $passport->recordEvidence(Evidence::create(
        EvidenceType::StudentReflection,
        hash('sha256', 'transfer-test'),
        $course->id()->value(),
        new DateTimeImmutable,
        [
            'lesson_title' => 'Misión final: una salida que cambia',
            'evidence_kind' => 'course_transfer_check',
            'score' => 3,
            'maximum_score' => 4,
            'strengths' => ['Percepción', 'Decisión', 'Autocuidado'],
            'practice_areas' => ['Convivencia'],
        ],
    ));
    app(RoadPassportRepository::class)->save($passport);
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->get(route('road-passport.show'))
        ->assertOk()
        ->assertSeeText('Evaluación de transferencia')
        ->assertSeeText('3 de 4 competencias integradas')
        ->assertSeeText('Percepción · Decisión · Autocuidado')
        ->assertSeeText('Para reforzar: Convivencia')
        ->assertSeeText('Del punto de partida a la transferencia')
        ->assertSeeText('1/3')
        ->assertSeeText('3/4');
});

it('muestra el pasaporte vial propio con nivel, estado e historial', function (): void {
    /** @var TestCase $this */
    $user = persistedRoadPassportWebTestUser();

    $passport = RoadPassport::create(
        id: RoadPassportId::fromString((string) Str::uuid()),
        userId: $user->id(),
    );
    $passport->changeLevel(2, new DateTimeImmutable);
    $course = createDraftCourseForPublishing('PASSPORT-CLOSURE-01');
    $passport->recordEvidence(Evidence::create(
        EvidenceType::LessonCompleted,
        (string) Str::uuid(),
        $course->id()->value(),
        new DateTimeImmutable,
        [
            'lesson_title' => 'Cruzar con calma',
            'reflection' => 'Voy a mirar de nuevo aunque otras personas ya estén cruzando.',
            'self_assessment' => 'puedo_aplicarlo',
        ],
    ));
    app(RoadPassportRepository::class)->save($passport);

    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $response = $this->get('/mi-pasaporte-vial');

    $response->assertOk();
    $response->assertSeeText('Activo');
    $response->assertSeeText('Nivel 2');
    $response->assertSeeText('Folio educativo');
    $response->assertDontSeeText('ID '.strtoupper(substr($passport->id()->value(), 0, 8)));
    $response->assertSeeText('Cambio de nivel');
    $response->assertSeeText('Puedo aplicarlo');
    $response->assertSeeText('Voy a mirar de nuevo aunque otras personas ya estén cruzando.');
    $response->assertSeeText('Aprendizaje digital (1)');
    $response->assertSeeText('Prácticas (0)');
    $response->assertSeeText('Todavía no hay prácticas registradas.');
    $response->assertSeeText('Imprimir o guardar como PDF');
    $response->assertSeeText('No sustituye una licencia de conducir');
    $response->assertSeeText('Copiar enlace de verificación');
    $response->assertSeeText('Compartir desde mi dispositivo');
    $response->assertSeeText('Abrir verificación pública');
    $response->assertSeeText('Mostrar código QR en pantalla');
    $response->assertSee(route('road-passport.verify'));
    $response->assertSee('Código QR para verificar este Pasaporte Vial');
    $response->assertSee('data:image/svg+xml;base64,', false);
});

it('permite reflexionar una sola vez sobre una práctica acompañada propia', function (): void {
    /** @var TestCase $this */
    $user = persistedRoadPassportWebTestUser();
    $guardian = persistedRoadPassportWebTestUser();
    UserModel::query()->whereKey($guardian->id())->update(['name' => 'Persona Acompañante']);
    $relationshipId = (string) Str::uuid();
    app(GuardianRelationshipRepository::class)->save(GuardianRelationship::create(
        id: $relationshipId,
        guardianUserId: $guardian->id(),
        minorUserId: $user->id(),
    ));
    $course = createDraftCourseForPublishing('PASSPORT-REFLECTION-01');
    $observationSubject = hash('sha256', 'observacion-propia');
    $passport = RoadPassport::create(
        id: RoadPassportId::fromString((string) Str::uuid()),
        userId: $user->id(),
    );
    $passport->recordEvidence(Evidence::create(
        EvidenceType::GuidedPracticeObserved,
        $observationSubject,
        $course->id()->value(),
        new DateTimeImmutable,
        [
            'lesson_title' => 'Cruce seguro',
            'behavior' => 'identified_risk',
            'practice_context' => 'controlled_space',
            'guardian_relationship_id' => $relationshipId,
        ],
    ));
    app(RoadPassportRepository::class)->save($passport);
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $payload = [
        'observation_subject_id' => $observationSubject,
        'learned' => 'Aprendí a detenerme y observar antes de cruzar.',
        'next_action' => 'La próxima vez aplicaré todos los pasos sin apresurarme.',
    ];
    $this->post(route('road-passport.reflections.store'), $payload)
        ->assertRedirect(route('road-passport.show'))
        ->assertSessionHas('status');
    $this->post(route('road-passport.reflections.store'), $payload);

    $restored = app(RoadPassportRepository::class)->findByUserId($user->id());
    assert($restored !== null);
    expect(array_filter(
        $restored->evidence(),
        static fn (Evidence $item): bool => $item->type === EvidenceType::StudentReflection,
    ))->toHaveCount(1);
    $this->get(route('road-passport.show'))
        ->assertOk()
        ->assertSeeText('Reflexión personal')
        ->assertSeeText('Entorno de práctica: Espacio controlado sin tránsito')
        ->assertSeeText('Validada por Persona Acompañante')
        ->assertSeeText('Aprendí a detenerme y observar antes de cruzar.')
        ->assertSeeText('Reflexión completada');
});

it('impide reflexionar sobre una observación ajena', function (): void {
    /** @var TestCase $this */
    $user = persistedRoadPassportWebTestUser();
    app(RoadPassportRepository::class)->save(RoadPassport::create(
        RoadPassportId::fromString((string) Str::uuid()),
        $user->id(),
    ));
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->post(route('road-passport.reflections.store'), [
        'observation_subject_id' => hash('sha256', 'observacion-ajena'),
        'learned' => 'Este aprendizaje no pertenece a mi pasaporte.',
        'next_action' => 'No debería poder guardar esta reflexión ajena.',
    ])->assertNotFound();
});

it('registra una sola práctica personal y la identifica como no verificada', function (): void {
    /** @var TestCase $this */
    $user = persistedRoadPassportWebTestUser();
    UserModel::query()->whereKey($user->id())->update(['date_of_birth' => '1990-01-01']);
    $course = createDraftCourseForPublishing('PASSPORT-SELF-PRACTICE');
    $lessonId = (string) Str::uuid();
    $passport = RoadPassport::create(RoadPassportId::fromString((string) Str::uuid()), $user->id());
    $passport->recordEvidence(Evidence::create(EvidenceType::LessonCompleted, $lessonId, $course->id()->value(), new DateTimeImmutable, ['lesson_title' => 'Cruce seguro', 'competency_id' => null, 'indicator_codes' => []]));
    app(RoadPassportRepository::class)->save($passport);
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');
    $payload = ['lesson_id' => $lessonId, 'behavior' => 'made_safe_decision', 'practice_context' => 'controlled_space', 'note' => 'Practiqué la decisión desde un lugar protegido.', 'safe_environment' => '1'];

    $this->post(route('road-passport.personal-practices.store'), $payload)->assertSessionHas('status');
    $this->post(route('road-passport.personal-practices.store'), $payload);

    $restored = app(RoadPassportRepository::class)->findByUserId($user->id());
    expect(array_filter($restored?->evidence() ?? [], static fn (Evidence $item): bool => $item->type === EvidenceType::SelfReportedPractice))->toHaveCount(1);
    $this->get(route('road-passport.show'))->assertOk()->assertSeeText('Práctica personal declarada')->assertSeeText('sin verificación externa');
});

it('impide que una persona menor registre prácticas personales sin acompañamiento', function (): void {
    /** @var TestCase $this */
    $user = persistedRoadPassportWebTestUser();
    UserModel::query()->whereKey($user->id())->update(['date_of_birth' => '2014-01-01']);
    $course = createDraftCourseForPublishing('PASSPORT-MINOR-SELF-PRACTICE');
    $lessonId = (string) Str::uuid();
    $passport = RoadPassport::create(RoadPassportId::fromString((string) Str::uuid()), $user->id());
    $passport->recordEvidence(Evidence::create(EvidenceType::LessonCompleted, $lessonId, $course->id()->value(), new DateTimeImmutable, ['lesson_title' => 'Cruce seguro', 'competency_id' => null, 'indicator_codes' => []]));
    app(RoadPassportRepository::class)->save($passport);
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->get(route('road-passport.show'))
        ->assertOk()
        ->assertDontSeeText('Registrar una práctica personal')
        ->assertSeeText('Esta práctica debe realizarse en un entorno seguro y ser registrada por una persona adulta acompañante.');

    $this->post(route('road-passport.personal-practices.store'), [
        'lesson_id' => $lessonId,
        'behavior' => 'made_safe_decision',
        'practice_context' => 'controlled_space',
        'note' => 'Practiqué la decisión desde un lugar protegido.',
        'safe_environment' => '1',
    ])->assertForbidden();

    $restored = app(RoadPassportRepository::class)->findByUserId($user->id());
    expect(array_filter($restored?->evidence() ?? [], static fn (Evidence $item): bool => $item->type === EvidenceType::SelfReportedPractice))->toHaveCount(0);
});

it('explica que debe completar la fecha de nacimiento antes de elegir la modalidad de práctica', function (): void {
    /** @var TestCase $this */
    $user = persistedRoadPassportWebTestUser();
    $course = createDraftCourseForPublishing('PASSPORT-UNKNOWN-AGE');
    $lessonId = (string) Str::uuid();
    $passport = RoadPassport::create(RoadPassportId::fromString((string) Str::uuid()), $user->id());
    $passport->recordEvidence(Evidence::create(EvidenceType::LessonCompleted, $lessonId, $course->id()->value(), new DateTimeImmutable, ['lesson_title' => 'Cruce seguro']));
    app(RoadPassportRepository::class)->save($passport);
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->get(route('road-passport.show'))
        ->assertOk()
        ->assertDontSeeText('Registrar una práctica personal')
        ->assertSeeText('completá tu fecha de nacimiento')
        ->assertSee(route('student-profile.show'));
});

it('permite verificar públicamente un resumen sin revelar información personal', function (): void {
    /** @var TestCase $this */
    $user = persistedRoadPassportWebTestUser();
    $course = createDraftCourseForPublishing('PASSPORT-PUBLIC-VERIFY');
    $passport = RoadPassport::create(RoadPassportId::fromString((string) Str::uuid()), $user->id());
    $passport->recordEvidence(Evidence::create(
        EvidenceType::LessonCompleted,
        (string) Str::uuid(),
        $course->id()->value(),
        new DateTimeImmutable,
        ['lesson_title' => 'Secreto de la persona estudiante'],
    ));
    $passport->recordEvidence(Evidence::create(
        EvidenceType::StudentReflection,
        hash('sha256', 'public-verification-reflection'),
        $course->id()->value(),
        new DateTimeImmutable,
        ['learned' => 'Esta reflexión es privada y no debe publicarse.'],
    ));
    app(RoadPassportRepository::class)->save($passport);
    $code = app(RoadPassportVerificationCode::class)->issue($passport->id()->value());

    $this->get(route('road-passport.verify', ['code' => $code]))
        ->assertOk()
        ->assertSeeText('Pasaporte auténtico')
        ->assertSeeText('Aprendizajes digitales')
        ->assertSeeText('Prácticas observadas')
        ->assertSeeText('Última evidencia')
        ->assertSeeText('Verificado en línea')
        ->assertSeeText('está vigente al momento de esta consulta')
        ->assertSeeText('Esta consulta protege la identidad')
        ->assertDontSeeText('Estudiante Vial')
        ->assertDontSeeText('Secreto de la persona estudiante')
        ->assertDontSeeText('Esta reflexión es privada');

    $this->get(route('road-passport.verify', ['code' => $passport->id()->value().'.firma-falsa']))
        ->assertOk()
        ->assertSeeText('El código no es válido');

    $passport->revoke('Reemplazado por una nueva trayectoria', new DateTimeImmutable);
    app(RoadPassportRepository::class)->save($passport);
    $this->get(route('road-passport.verify', ['code' => $code]))
        ->assertOk()
        ->assertSeeText('Revocado')
        ->assertSeeText('no está vigente');
});

it('permite renovar el enlace y rechaza inmediatamente el anterior', function (): void {
    /** @var TestCase $this */
    $user = persistedRoadPassportWebTestUser();
    $passport = RoadPassport::create(RoadPassportId::fromString((string) Str::uuid()), $user->id());
    app(RoadPassportRepository::class)->save($passport);
    $codes = app(RoadPassportVerificationCode::class);
    $oldCode = $codes->issue($passport->id()->value(), 1);
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->post(route('road-passport.verification.rotate'))
        ->assertRedirect(route('road-passport.show'))
        ->assertSessionHas('status');

    $restored = app(RoadPassportRepository::class)->findByUserId($user->id());
    expect($restored?->verificationVersion())->toBe(2);
    $newCode = $codes->issue($passport->id()->value(), 2);

    $this->get(route('road-passport.verify', ['code' => $oldCode]))
        ->assertOk()
        ->assertSeeText('El código no es válido');
    $this->get(route('road-passport.verify', ['code' => $newCode]))
        ->assertOk()
        ->assertSeeText('Pasaporte auténtico');
});
