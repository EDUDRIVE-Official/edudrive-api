<?php

declare(strict_types=1);

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

function actingAsCurriculumRole(Role $role): void
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario de curriculo',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);
    app(RoleAssignmentRepository::class)->save(RoleAssignment::assign(
        id: (string) Str::uuid(),
        userId: $user->id(),
        role: $role,
        organizationId: null,
    ));
    Sanctum::actingAs(UserModel::query()->findOrFail($user->id()));
}

function createCurriculumDraft(string $code = 'CURRICULUM-01'): Course
{
    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString($code),
        title: CourseTitle::fromString('Curso curricular'),
    );
    app(CourseRepository::class)->save($course);

    return $course;
}

/**
 * @return array{modules: list<array<string, mixed>>}
 */
function validCurriculumPayload(): array
{
    $firstModuleId = (string) Str::uuid();
    $secondModuleId = (string) Str::uuid();
    $firstUnitId = (string) Str::uuid();
    $secondUnitId = (string) Str::uuid();
    $thirdUnitId = (string) Str::uuid();

    return [
        'modules' => [
            [
                'id' => $firstModuleId,
                'code' => 'mod-01',
                'title' => ' Fundamentos ',
                'description' => ' Bases de seguridad vial. ',
                'objectives' => ' Reconocer riesgos frecuentes. ',
                'duration_minutes' => 90,
                'position' => 1,
                'prerequisite_module_ids' => [],
                'units' => [
                    [
                        'id' => $firstUnitId,
                        'code' => 'uni-01',
                        'title' => ' Percepcion del riesgo ',
                        'description' => ' Identificacion de peligros. ',
                        'objectives' => null,
                        'duration_minutes' => 30,
                        'position' => 1,
                        'prerequisite_unit_ids' => [],
                    ],
                    [
                        'id' => $secondUnitId,
                        'code' => 'uni-02',
                        'title' => ' Decision segura ',
                        'description' => ' Seleccion de respuestas seguras. ',
                        'objectives' => ' Elegir una maniobra segura. ',
                        'duration_minutes' => 45,
                        'position' => 2,
                        'prerequisite_unit_ids' => [$firstUnitId],
                    ],
                ],
            ],
            [
                'id' => $secondModuleId,
                'code' => 'mod-02',
                'title' => 'Aplicacion',
                'description' => 'Aplicacion de decisiones seguras.',
                'objectives' => null,
                'duration_minutes' => null,
                'position' => 2,
                'prerequisite_module_ids' => [$firstModuleId],
                'units' => [
                    [
                        'id' => $thirdUnitId,
                        // El codigo se puede repetir en otro modulo.
                        'code' => 'uni-01',
                        'title' => 'Aplicacion integrada',
                        'description' => 'Resolucion de una situacion vial.',
                        'objectives' => null,
                        'duration_minutes' => 60,
                        'position' => 1,
                        'prerequisite_unit_ids' => [$secondUnitId],
                    ],
                ],
            ],
        ],
    ];
}

/** @return array{modules: list<array<string, mixed>>} */
function curriculumPayloadAtAggregateLimits(): array
{
    $moduleIds = array_map(static fn (): string => (string) Str::uuid(), range(1, 200));
    $remainingPrerequisites = 5000;
    $modules = [];

    foreach ($moduleIds as $moduleIndex => $moduleId) {
        $prerequisiteCount = min($moduleIndex, $remainingPrerequisites, 200);
        $remainingPrerequisites -= $prerequisiteCount;
        $units = [];

        foreach (range(1, 5) as $unitPosition) {
            $units[] = [
                'id' => (string) Str::uuid(),
                'code' => "uni-{$unitPosition}",
                'title' => "Unidad {$unitPosition}",
                'description' => 'Contenido de la unidad.',
                'objectives' => null,
                'duration_minutes' => 15,
                'position' => $unitPosition,
                'prerequisite_unit_ids' => [],
            ];
        }

        $modules[] = [
            'id' => $moduleId,
            'code' => sprintf('mod-%03d', $moduleIndex + 1),
            'title' => sprintf('Modulo %03d', $moduleIndex + 1),
            'description' => 'Contenido del modulo.',
            'objectives' => null,
            'duration_minutes' => 75,
            'position' => $moduleIndex + 1,
            'prerequisite_module_ids' => array_slice($moduleIds, 0, $prerequisiteCount),
            'units' => $units,
        ];
    }

    expect($remainingPrerequisites)->toBe(0);

    return ['modules' => $modules];
}

it('reemplaza consulta publica e inmoviliza el curriculo completo de un curso', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();

    $created = $this->postJson('/api/v1/academic/courses', [
        'code' => 'CURRICULUM-FLOW',
        'title' => 'Curso con curriculo',
    ])->assertCreated();
    $courseId = (string) $created->json('data.id');
    $payload = validCurriculumPayload();

    $storedResponse = $this->putJson("/api/v1/academic/courses/{$courseId}/curriculum", $payload)
        ->assertOk()
        ->assertJsonPath('data.id', $courseId)
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.modules.0.code', 'MOD-01')
        ->assertJsonPath('data.modules.0.title', 'Fundamentos')
        ->assertJsonPath('data.modules.0.units.1.code', 'UNI-02')
        ->assertJsonPath('data.modules.1.code', 'MOD-02')
        ->assertJsonPath('data.modules.1.units.0.code', 'UNI-01');
    $storedData = $storedResponse->json('data');

    $stored = app(CourseRepository::class)->findById(CourseId::fromString($courseId));
    expect($stored)->not->toBeNull()
        ->and($stored?->modules())->toHaveCount(2)
        ->and($stored?->modules()[0]->units())->toHaveCount(2)
        ->and($stored?->modules()[1]->prerequisiteModuleIds()[0]->value())
        ->toBe($payload['modules'][0]['id']);

    addCompleteContentForCourse($stored);

    $this->getJson("/api/v1/academic/courses/{$courseId}/curriculum")
        ->assertOk()
        ->assertJsonPath('data', $storedData);

    approveCourseThroughReviewFlow($this, $courseId);

    $this->postJson("/api/v1/academic/courses/{$courseId}/publish")
        ->assertOk()
        ->assertJsonPath('data.status', 'published');

    $changedPayload = $payload;
    $changedPayload['modules'][0]['title'] = 'Cambio no permitido';

    $this->putJson("/api/v1/academic/courses/{$courseId}/curriculum", $changedPayload)
        ->assertUnprocessable()
        ->assertJsonPath('code', 'COURSE_CURRICULUM_CANNOT_BE_MODIFIED');

    $this->getJson("/api/v1/academic/courses/{$courseId}/curriculum")
        ->assertOk()
        ->assertJsonPath('data.modules', $storedData['modules']);
});

it('devuelve en PUT y GET el mismo orden canonico de prerrequisitos', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();
    $course = createCurriculumDraft('CURRICULUM-CANONICAL');
    $payload = validCurriculumPayload();
    $firstModuleId = (string) $payload['modules'][0]['id'];
    $secondModuleId = (string) $payload['modules'][1]['id'];
    $firstUnitId = (string) $payload['modules'][0]['units'][0]['id'];
    $secondUnitId = (string) $payload['modules'][0]['units'][1]['id'];
    $payload['modules'][] = [
        'id' => (string) Str::uuid(),
        'code' => 'mod-03',
        'title' => 'Cierre',
        'description' => 'Integracion del curso.',
        'objectives' => null,
        'duration_minutes' => 30,
        'position' => 3,
        'prerequisite_module_ids' => [$secondModuleId, $firstModuleId],
        'units' => [[
            'id' => (string) Str::uuid(),
            'code' => 'uni-01',
            'title' => 'Cierre integrado',
            'description' => 'Integra aprendizajes previos.',
            'objectives' => null,
            'duration_minutes' => 30,
            'position' => 1,
            'prerequisite_unit_ids' => [$secondUnitId, $firstUnitId],
        ]],
    ];
    $url = "/api/v1/academic/courses/{$course->id()->value()}/curriculum";

    $putData = $this->putJson($url, $payload)->assertOk()->json('data');
    $getData = $this->getJson($url)->assertOk()->json('data');

    expect($putData)->toBe($getData)
        ->and($putData['modules'][2]['prerequisite_module_ids'])->toBe([$firstModuleId, $secondModuleId])
        ->and($putData['modules'][2]['units'][0]['prerequisite_unit_ids'])->toBe([$firstUnitId, $secondUnitId]);
});

it('protege lectura y escritura con autenticacion y permisos separados', function (): void {
    /** @var TestCase $this */
    $course = createCurriculumDraft('CURRICULUM-AUTH');
    $url = "/api/v1/academic/courses/{$course->id()->value()}/curriculum";

    $this->getJson($url)->assertUnauthorized();
    $this->putJson($url, ['modules' => []])->assertUnauthorized();

    actingAsCurriculumRole(Role::Teacher);

    $this->getJson($url)
        ->assertOk()
        ->assertJsonPath('data.modules', []);
    $this->putJson($url, ['modules' => []])->assertForbidden();
});

it('valida uuid y codigos duplicados por casing en su alcance correcto', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();
    $course = createCurriculumDraft('CURRICULUM-DUPLICATES');
    $url = "/api/v1/academic/courses/{$course->id()->value()}/curriculum";
    $payload = validCurriculumPayload();

    $payload['modules'][1]['id'] = strtoupper((string) $payload['modules'][0]['id']);
    $payload['modules'][1]['code'] = strtoupper((string) $payload['modules'][0]['code']);
    $payload['modules'][1]['units'][0]['id'] = strtoupper((string) $payload['modules'][0]['units'][0]['id']);
    $payload['modules'][0]['units'][1]['code'] = strtoupper((string) $payload['modules'][0]['units'][0]['code']);

    $this->putJson($url, $payload)
        ->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonValidationErrors([
            'modules.0.id',
            'modules.0.code',
            'modules.0.units.0.id',
            'modules.1.units.0.id',
            'modules.0.units.0.code',
        ]);
});

it('rechaza identificadores curriculares no escalares como error de validacion', function (string $path): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();
    $course = createCurriculumDraft('CURRICULUM-MALFORMED-'.Str::random(8));
    $payload = validCurriculumPayload();

    if ($path === 'modules.0.id') {
        $payload['modules'][0]['id'] = [];
    } elseif ($path === 'modules.0.code') {
        $payload['modules'][0]['code'] = [];
    } else {
        $payload['modules'][0]['units'][0]['id'] = [];
    }

    $this->putJson("/api/v1/academic/courses/{$course->id()->value()}/curriculum", $payload)
        ->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonValidationErrors([$path]);
})->with([
    'module id' => ['modules.0.id'],
    'module code' => ['modules.0.code'],
    'unit id' => ['modules.0.units.0.id'],
]);

it('limita el tamano del payload curricular', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();
    $course = createCurriculumDraft('CURRICULUM-SIZE');

    $response = $this->putJson("/api/v1/academic/courses/{$course->id()->value()}/curriculum", [
        'modules' => array_fill(0, 201, []),
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonValidationErrors(['modules']);

    expect(array_keys($response->json('errors')))->toBe(['modules']);
});

it('rechaza temprano mas de mil unidades totales', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();
    $course = createCurriculumDraft('CURRICULUM-UNIT-LIMIT');

    $this->putJson("/api/v1/academic/courses/{$course->id()->value()}/curriculum", [
        'modules' => [
            ['units' => array_fill(0, 500, [])],
            ['units' => array_fill(0, 500, [])],
            ['units' => [[]]],
        ],
    ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonValidationErrors(['modules']);

    expect(app(CourseRepository::class)->findById($course->id())?->modules())->toBe([]);
});

it('rechaza temprano mas de cinco mil referencias de prerrequisito', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();
    $course = createCurriculumDraft('CURRICULUM-REFERENCE-LIMIT');

    $this->putJson("/api/v1/academic/courses/{$course->id()->value()}/curriculum", [
        'modules' => [[
            'prerequisite_module_ids' => array_fill(0, 2500, 'not-expanded'),
            'units' => [[
                'prerequisite_unit_ids' => array_fill(0, 2501, 'not-expanded'),
            ]],
        ]],
    ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonValidationErrors(['modules']);

    expect(app(CourseRepository::class)->findById($course->id())?->modules())->toBe([]);
});

it('acepta exactamente los limites agregados de unidades y prerrequisitos', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();
    $course = createCurriculumDraft('CURRICULUM-EXACT-LIMITS');

    $this->putJson(
        "/api/v1/academic/courses/{$course->id()->value()}/curriculum",
        curriculumPayloadAtAggregateLimits(),
    )
        ->assertOk()
        ->assertJsonCount(200, 'data.modules')
        ->assertJsonCount(5, 'data.modules.199.units');
});

it('delega al dominio las posiciones no consecutivas', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();
    $course = createCurriculumDraft('CURRICULUM-POSITION');
    $payload = validCurriculumPayload();
    $payload['modules'][0]['position'] = 2;

    $this->putJson("/api/v1/academic/courses/{$course->id()->value()}/curriculum", $payload)
        ->assertUnprocessable()
        ->assertJsonPath('code', 'INVALID_CURRICULUM_POSITION');
});

it('rechaza prerrequisitos futuros e inexistentes desde el dominio', function (string $reference): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();
    $course = createCurriculumDraft('CURRICULUM-REFERENCE-'.$reference);
    $payload = validCurriculumPayload();
    $payload['modules'][0]['prerequisite_module_ids'] = [
        $reference === 'FUTURE'
            ? $payload['modules'][1]['id']
            : (string) Str::uuid(),
    ];

    $this->putJson("/api/v1/academic/courses/{$course->id()->value()}/curriculum", $payload)
        ->assertUnprocessable()
        ->assertJsonPath('code', 'INVALID_CURRICULUM_PREREQUISITE');
})->with(['FUTURE', 'UNKNOWN']);

it('rechaza publicar un modulo sin unidades', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();
    $course = createCurriculumDraft('CURRICULUM-EMPTY-MODULE');
    $payload = validCurriculumPayload();
    $payload['modules'][0]['units'] = [];
    $payload['modules'][1]['prerequisite_module_ids'] = [$payload['modules'][0]['id']];
    $payload['modules'][1]['units'][0]['prerequisite_unit_ids'] = [];

    $this->putJson("/api/v1/academic/courses/{$course->id()->value()}/curriculum", $payload)
        ->assertOk();
    approveCourseThroughReviewFlow($this, $course->id()->value());
    $this->postJson("/api/v1/academic/courses/{$course->id()->value()}/publish")
        ->assertUnprocessable()
        ->assertJsonPath('code', 'COURSE_MODULE_REQUIRES_UNITS');
});

it('rechaza consultar o reemplazar el curriculo de un curso inexistente', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();
    $courseId = (string) Str::uuid();

    $this->getJson("/api/v1/academic/courses/{$courseId}/curriculum")
        ->assertNotFound()
        ->assertJsonPath('code', 'COURSE_NOT_FOUND');
    $this->putJson("/api/v1/academic/courses/{$courseId}/curriculum", ['modules' => []])
        ->assertNotFound()
        ->assertJsonPath('code', 'COURSE_NOT_FOUND');
});

it('rechaza publicar un curso con curriculo vacio', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();
    $course = createCurriculumDraft('CURRICULUM-EMPTY');

    $this->putJson("/api/v1/academic/courses/{$course->id()->value()}/curriculum", [
        'modules' => [],
    ])->assertOk();
    approveCourseThroughReviewFlow($this, $course->id()->value());
    $this->postJson("/api/v1/academic/courses/{$course->id()->value()}/publish")
        ->assertUnprocessable()
        ->assertJsonPath('code', 'COURSE_CURRICULUM_REQUIRED');
});
