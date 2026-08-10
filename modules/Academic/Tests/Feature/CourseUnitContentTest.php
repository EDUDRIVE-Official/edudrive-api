<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Entities\CourseModule;
use Modules\Academic\Domain\Entities\CourseUnit;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseModuleId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;
use Modules\Academic\Domain\ValueObjects\CurriculumCode;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

/** @return array{0: Course, 1: string, 2: string} */
function createCourseForUnitContent(string $code = 'CONTENT-01'): array
{
    $firstUnitId = (string) Str::uuid();
    $secondUnitId = (string) Str::uuid();
    $course = Course::create(
        CourseId::fromString((string) Str::uuid()),
        CourseCode::fromString($code),
        CourseTitle::fromString('Curso con contenido'),
    );
    $course->replaceCurriculum([
        CourseModule::create(
            CourseModuleId::fromString((string) Str::uuid()),
            CurriculumCode::fromString('MOD-01'),
            'Modulo',
            'Modulo de prueba.',
            null,
            60,
            1,
            [],
            [
                CourseUnit::create(CourseUnitId::fromString($firstUnitId), CurriculumCode::fromString('UNI-01'), 'Unidad uno', 'Primera.', null, 30, 1, []),
                CourseUnit::create(CourseUnitId::fromString($secondUnitId), CurriculumCode::fromString('UNI-02'), 'Unidad dos', 'Segunda.', null, 30, 2, []),
            ],
        ),
    ]);
    app(CourseRepository::class)->save($course);

    return [$course, $firstUnitId, $secondUnitId];
}

function actingAsUnitContentRole(Role $role): void
{
    $user = User::register((string) Str::uuid(), 'Usuario contenido', Email::fromString(Str::uuid().'@edudrive.cr'), 'hash');
    app(UserRepository::class)->save($user);
    app(RoleAssignmentRepository::class)->save(RoleAssignment::assign((string) Str::uuid(), $user->id(), $role, null));
    Sanctum::actingAs(UserModel::query()->findOrFail($user->id()));
}

/** @return array{lessons: list<array<string, mixed>>} */
function validUnitContentPayload(): array
{
    $blocks = [
        ['id' => (string) Str::uuid(), 'type' => 'text', 'position' => 1, 'payload' => ['markdown' => '# Introduccion', 'title' => 'Texto']],
        ['id' => (string) Str::uuid(), 'type' => 'image', 'position' => 2, 'payload' => ['url' => 'https://cdn.edudrive.test/image.png', 'alt' => 'Cruce peatonal', 'caption' => 'Cruce seguro']],
        ['id' => (string) Str::uuid(), 'type' => 'video', 'position' => 3, 'payload' => ['url' => 'https://cdn.edudrive.test/video.mp4', 'captions_url' => 'https://cdn.edudrive.test/video.vtt', 'transcript' => 'Transcripcion', 'title' => 'Video', 'description' => 'Descripcion']],
        ['id' => (string) Str::uuid(), 'type' => 'audio', 'position' => 4, 'payload' => ['url' => 'https://cdn.edudrive.test/audio.mp3', 'transcript' => 'Transcripcion audio', 'title' => 'Audio', 'description' => 'Descripcion']],
        ['id' => (string) Str::uuid(), 'type' => 'interactive', 'position' => 5, 'payload' => ['url' => 'https://content.edudrive.test/activity', 'accessible_text' => 'Alternativa accesible', 'title' => 'Actividad', 'description' => 'Descripcion']],
        ['id' => (string) Str::uuid(), 'type' => 'download', 'position' => 6, 'payload' => ['url' => 'https://cdn.edudrive.test/guide.pdf', 'display_name' => 'Guia', 'mime_type' => 'application/pdf', 'description' => 'Material', 'filename' => 'guia.pdf', 'size_bytes' => 123]],
    ];

    return ['lessons' => [[
        'id' => (string) Str::uuid(), 'code' => 'lec-01', 'title' => ' Leccion ', 'summary' => ' Resumen ',
        'duration_minutes' => 20, 'position' => 1, 'blocks' => $blocks,
    ]]];
}

/** @return array{lessons: list<array<string, mixed>>} */
function unitContentPayloadAtLimits(int $lessonCount, int $blocksPerLesson): array
{
    $lessons = [];
    for ($lessonPosition = 1; $lessonPosition <= $lessonCount; $lessonPosition++) {
        $blocks = [];
        for ($blockPosition = 1; $blockPosition <= $blocksPerLesson; $blockPosition++) {
            $blocks[] = [
                'id' => (string) Str::uuid(),
                'type' => 'text',
                'position' => $blockPosition,
                'payload' => ['markdown' => "Contenido {$lessonPosition}-{$blockPosition}"],
            ];
        }
        $lessons[] = [
            'id' => (string) Str::uuid(),
            'code' => sprintf('LEC-%03d', $lessonPosition),
            'title' => "Leccion {$lessonPosition}",
            'summary' => null,
            'duration_minutes' => 10,
            'position' => $lessonPosition,
            'blocks' => $blocks,
        ];
    }

    return ['lessons' => $lessons];
}

it('expone contenido protegido y conserva la respuesta canonica entre PUT y GET', function (): void {
    /** @var TestCase $this */
    [$course, $unitId] = createCourseForUnitContent();
    $url = "/api/v1/academic/courses/{$course->id()->value()}/units/{$unitId}/content";

    $this->getJson($url)->assertUnauthorized();
    $this->putJson($url, ['lessons' => []])->assertUnauthorized();

    actingAsSuperAdminUser();
    $this->getJson($url)->assertOk()->assertJsonPath('data.lessons', []);
    $put = $this->putJson($url, validUnitContentPayload())->assertOk()->json('data');
    $get = $this->getJson($url)->assertOk()->json('data');

    expect($put)->toBe($get)
        ->and($put['lessons'][0]['code'])->toBe('LEC-01')
        ->and($put['lessons'][0]['blocks'])->toHaveCount(6);
});

it('separa permisos de lectura y escritura', function (): void {
    /** @var TestCase $this */
    [$course, $unitId] = createCourseForUnitContent('CONTENT-AUTH');
    $url = "/api/v1/academic/courses/{$course->id()->value()}/units/{$unitId}/content";
    actingAsUnitContentRole(Role::Teacher);
    $this->getJson($url)->assertOk();
    $this->putJson($url, ['lessons' => []])->assertForbidden();
});

it('impide reemplazar contenido publicado y preserva lo almacenado', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();
    [$course, $firstUnitId, $secondUnitId] = createCourseForUnitContent('CONTENT-PUBLISH');
    $firstUrl = "/api/v1/academic/courses/{$course->id()->value()}/units/{$firstUnitId}/content";
    $secondUrl = "/api/v1/academic/courses/{$course->id()->value()}/units/{$secondUnitId}/content";
    $payload = validUnitContentPayload();
    $stored = $this->putJson($firstUrl, $payload)->assertOk()->json('data');
    approveCourseThroughReviewFlow($this, $course->id()->value());

    $this->postJson("/api/v1/academic/courses/{$course->id()->value()}/publish")
        ->assertUnprocessable()->assertJsonPath('code', 'COURSE_UNIT_CONTENT_REQUIRED');

    $this->postJson("/api/v1/academic/courses/{$course->id()->value()}/send-back-to-draft")
        ->assertOk()->assertJsonPath('data.status', 'draft');
    $this->putJson($secondUrl, validUnitContentPayload())->assertOk();
    approveCourseThroughReviewFlow($this, $course->id()->value());
    $this->postJson("/api/v1/academic/courses/{$course->id()->value()}/publish")->assertOk();
    $this->putJson($firstUrl, ['lessons' => []])->assertUnprocessable();
    $this->getJson($firstUrl)->assertOk()->assertJsonPath('data.lessons', $stored['lessons']);
});

it('devuelve errores publicos para curso unidad y UUID de ruta invalidos', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();
    [$course, $unitId] = createCourseForUnitContent('CONTENT-ERRORS');
    $missingCourse = (string) Str::uuid();
    $this->getJson("/api/v1/academic/courses/{$missingCourse}/units/{$unitId}/content")
        ->assertNotFound()->assertJsonPath('code', 'COURSE_NOT_FOUND');
    $this->getJson("/api/v1/academic/courses/{$course->id()->value()}/units/".Str::uuid().'/content')
        ->assertNotFound()->assertJsonPath('code', 'COURSE_UNIT_NOT_FOUND');
    $this->getJson("/api/v1/academic/courses/not-a-uuid/units/{$unitId}/content")->assertNotFound();
});

it('delega posiciones invalidas al dominio', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();
    [$course, $unitId] = createCourseForUnitContent('CONTENT-POSITIONS');
    $payload = validUnitContentPayload();
    $payload['lessons'][0]['position'] = 2;
    $this->putJson("/api/v1/academic/courses/{$course->id()->value()}/units/{$unitId}/content", $payload)
        ->assertUnprocessable()->assertJsonPath('code', 'INVALID_LESSON_POSITION');
});

it('rechaza entradas malformadas y duplicados sin producir 500', function (array $mutations, array $errors): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();
    [$course, $unitId] = createCourseForUnitContent('CONTENT-VALID-'.Str::random(8));
    $payload = validUnitContentPayload();
    foreach ($mutations as [$path, $value]) {
        data_set($payload, $path, $value);
    }
    $this->putJson("/api/v1/academic/courses/{$course->id()->value()}/units/{$unitId}/content", $payload)
        ->assertUnprocessable()->assertJsonPath('code', 'VALIDATION_ERROR')->assertJsonValidationErrors($errors);
})->with([
    'lessons scalar' => [[['lessons', 'invalid']], ['lessons']],
    'payload scalar' => [[['lessons.0.blocks.0.payload', 'invalid']], ['lessons.0.blocks.0.payload']],
]);

it('rechaza codigos de leccion e IDs de bloque duplicados ignorando casing', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();
    [$course, $unitId] = createCourseForUnitContent('CONTENT-DUPLICATES');
    $payload = validUnitContentPayload();
    $duplicate = $payload['lessons'][0];
    $duplicate['id'] = (string) Str::uuid();
    $duplicate['code'] = strtoupper((string) $payload['lessons'][0]['code']);
    $duplicate['position'] = 2;
    $duplicate['blocks'][0]['id'] = strtoupper((string) $payload['lessons'][0]['blocks'][0]['id']);
    $payload['lessons'][] = $duplicate;

    $this->putJson("/api/v1/academic/courses/{$course->id()->value()}/units/{$unitId}/content", $payload)
        ->assertUnprocessable()
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonValidationErrors([
            'lessons.0.code', 'lessons.1.code',
            'lessons.0.blocks.0.id', 'lessons.1.blocks.0.id',
        ]);
});

it('rechaza tipos payloads y URLs invalidos en validacion HTTP', function (string $type, array $payload): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();
    [$course, $unitId] = createCourseForUnitContent('CONTENT-TYPE-'.Str::random(8));
    $input = validUnitContentPayload();
    $input['lessons'][0]['blocks'] = [[
        'id' => (string) Str::uuid(), 'type' => $type, 'position' => 1, 'payload' => $payload,
    ]];
    $this->putJson("/api/v1/academic/courses/{$course->id()->value()}/units/{$unitId}/content", $input)
        ->assertUnprocessable()->assertJsonPath('code', 'VALIDATION_ERROR');
})->with([
    'text html' => ['text', ['markdown' => '<script>alert(1)</script>']],
    'image no alt' => ['image', ['url' => 'https://cdn.test/a.png']],
    'video no captions' => ['video', ['url' => 'https://cdn.test/a.mp4', 'transcript' => 'x']],
    'audio no transcript' => ['audio', ['url' => 'https://cdn.test/a.mp3']],
    'interactive no alternative' => ['interactive', ['url' => 'https://cdn.test/a']],
    'download invalid size' => ['download', ['url' => 'https://cdn.test/a', 'display_name' => 'A', 'mime_type' => 'application/pdf', 'size_bytes' => 0]],
    'http scheme' => ['image', ['url' => 'http://cdn.test/a.png', 'alt' => 'A']],
    'unknown type' => ['iframe', ['url' => 'https://cdn.test/a']],
]);

it('rechaza limites agregados temprano', function (array $payload): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();
    [$course, $unitId] = createCourseForUnitContent('CONTENT-LIMIT-'.Str::random(8));
    $response = $this->putJson("/api/v1/academic/courses/{$course->id()->value()}/units/{$unitId}/content", $payload)
        ->assertUnprocessable()->assertJsonPath('code', 'VALIDATION_ERROR')->assertJsonValidationErrors(['lessons']);
    expect(array_keys($response->json('errors')))->toBe(['lessons']);
})->with([
    '101 lessons' => [['lessons' => array_fill(0, 101, [])]],
    '201 blocks' => [['lessons' => [['blocks' => array_fill(0, 201, [])]]]],
    '1001 total blocks' => [['lessons' => array_fill(0, 6, ['blocks' => array_fill(0, 167, [])])]],
]);

it('acepta exactamente los limites de lecciones bloques por leccion y bloques totales', function (int $lessons, int $blocks): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();
    [$course, $unitId] = createCourseForUnitContent('CONTENT-EXACT-'.Str::random(8));
    $this->putJson(
        "/api/v1/academic/courses/{$course->id()->value()}/units/{$unitId}/content",
        unitContentPayloadAtLimits($lessons, $blocks),
    )->assertOk()->assertJsonCount($lessons, 'data.lessons');
})->with([
    '100 lessons' => [100, 1],
    '200 blocks per lesson' => [1, 200],
    '1000 total blocks' => [5, 200],
]);

it('revierte por completo el reuso de un identificador perteneciente a otra unidad', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();
    [$firstCourse, $firstUnit] = createCourseForUnitContent('CONTENT-ID-A');
    $first = validUnitContentPayload();
    $this->putJson("/api/v1/academic/courses/{$firstCourse->id()->value()}/units/{$firstUnit}/content", $first)->assertOk();

    [$secondCourse, $secondUnit] = createCourseForUnitContent('CONTENT-ID-B');
    $second = validUnitContentPayload();
    $second['lessons'][0]['id'] = $first['lessons'][0]['id'];
    $this->putJson("/api/v1/academic/courses/{$secondCourse->id()->value()}/units/{$secondUnit}/content", $second)
        ->assertStatus(409)->assertJsonPath('code', 'COURSE_CONTENT_ID_CONFLICT');
    $this->getJson("/api/v1/academic/courses/{$secondCourse->id()->value()}/units/{$secondUnit}/content")
        ->assertOk()->assertJsonPath('data.lessons', []);
});

it('mantiene compatible la lectura de un curso publicado legado sin filas de contenido', function (): void {
    /** @var TestCase $this */
    actingAsSuperAdminUser();
    [$course, $unitId] = createCourseForUnitContent('CONTENT-LEGACY');
    approveCourseForPublishing($course);
    $course->publish(new DateTimeImmutable, completeCoverageForCourse($course));
    app(CourseRepository::class)->save($course);

    $this->getJson("/api/v1/academic/courses/{$course->id()->value()}/units/{$unitId}/content")
        ->assertOk()
        ->assertJsonPath('data.course_status', 'published')
        ->assertJsonPath('data.lessons', []);
});
