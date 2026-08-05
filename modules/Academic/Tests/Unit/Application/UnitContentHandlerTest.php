<?php

declare(strict_types=1);

use Modules\Academic\Application\Commands\ReplaceUnitContentCommand;
use Modules\Academic\Application\DTO\ContentBlockInput;
use Modules\Academic\Application\DTO\LessonInput;
use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Application\Exceptions\CourseUnitNotFound;
use Modules\Academic\Application\Queries\GetUnitContentQuery;
use Modules\Academic\Application\Responses\UnitContentResponse;
use Modules\Academic\Application\UseCases\GetUnitContentHandler;
use Modules\Academic\Application\UseCases\ReplaceUnitContentHandler;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\UnitContent;
use Modules\Academic\Domain\Entities\ContentBlocks\DownloadContentBlock;
use Modules\Academic\Domain\Entities\ContentBlocks\TextContentBlock;
use Modules\Academic\Domain\Entities\CourseModule;
use Modules\Academic\Domain\Entities\CourseUnit;
use Modules\Academic\Domain\Enums\CourseStatus;
use Modules\Academic\Domain\Exceptions\CourseContentCannotBeModified;
use Modules\Academic\Domain\Exceptions\InvalidContentBlock;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseModuleId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;
use Modules\Academic\Domain\ValueObjects\CurriculumCode;
use Modules\Academic\Domain\ValueObjects\UnitContentCoverage;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;

final class Eng028ContentCourseRepository implements CourseRepository
{
    /** @var array<string, Course> */
    private array $courses = [];

    /** @param list<Course> $courses */
    public function __construct(array $courses = [])
    {
        foreach ($courses as $course) {
            $this->courses[$course->id()->value()] = $course;
        }
    }

    public function save(Course $course): void
    {
        $this->courses[$course->id()->value()] = $course;
    }

    public function updateAtomically(CourseId $id, Closure $mutation): ?Course
    {
        $course = $this->findById($id);

        if ($course === null) {
            return null;
        }

        $candidate = clone $course;
        $mutation($candidate);
        $this->courses[$id->value()] = $candidate;

        return $candidate;
    }

    public function updateAtomicallyWithContentCoverage(CourseId $id, Closure $mutation): ?Course
    {
        $course = $this->findById($id);

        if ($course === null) {
            return null;
        }

        $candidate = clone $course;
        $mutation($candidate, UnitContentCoverage::fromUnitIds([]));
        $this->courses[$id->value()] = $candidate;

        return $candidate;
    }

    public function findById(CourseId $id): ?Course
    {
        return $this->courses[$id->value()] ?? null;
    }

    public function findByCode(CourseCode $code): ?Course
    {
        foreach ($this->courses as $course) {
            if ($course->code()->equals($code)) {
                return $course;
            }
        }

        return null;
    }

    public function existsByCode(CourseCode $code): bool
    {
        return $this->findByCode($code) !== null;
    }

    public function all(): array
    {
        return array_values($this->courses);
    }
}

final class Eng028UnitContentRepository implements UnitContentRepository
{
    public int $replaceCalls = 0;

    public bool $courseDisappearsDuringReplace = false;

    /** @var array<string, UnitContent> */
    private array $contents = [];

    public function __construct(private readonly CourseRepository $courses) {}

    public function findForCourseUnit(CourseId $courseId, CourseUnitId $unitId): ?UnitContent
    {
        $course = $this->courses->findById($courseId);

        if ($course === null) {
            return null;
        }

        if (! $course->ownsUnit($unitId)) {
            throw CourseUnitNotFound::create();
        }

        return $this->contents[$unitId->value()] ?? UnitContent::create($unitId, []);
    }

    public function replaceAtomically(CourseId $courseId, CourseUnitId $unitId, UnitContent $content): ?UnitContent
    {
        $this->replaceCalls++;

        if ($this->courseDisappearsDuringReplace) {
            return null;
        }

        $course = $this->courses->findById($courseId);

        if ($course === null) {
            return null;
        }

        if (! $course->ownsUnit($unitId)) {
            throw CourseUnitNotFound::create();
        }

        $course->ensureContentCanBeModified();
        $this->contents[$unitId->value()] = $content;

        return $content;
    }
}

function eng028ContentCourse(CourseStatus $status = CourseStatus::Draft): Course
{
    $module = CourseModule::create(
        id: CourseModuleId::fromString('019c2c00-0000-7000-8000-000000000010'),
        code: CurriculumCode::fromString('MOD-01'),
        title: 'Fundamentos',
        description: 'Fundamentos viales.',
        objectives: null,
        durationMinutes: 60,
        position: 1,
        prerequisiteModuleIds: [],
        units: [CourseUnit::create(
            id: CourseUnitId::fromString('019c2c00-0000-7000-8000-000000000100'),
            code: CurriculumCode::fromString('UNI-01'),
            title: 'Riesgos',
            description: 'Percepcion de riesgos.',
            objectives: null,
            durationMinutes: 30,
            position: 1,
            prerequisiteUnitIds: [],
        )],
    );

    return Course::restore(
        id: CourseId::fromString('019c2c00-0000-7000-8000-000000000001'),
        code: CourseCode::fromString('ROAD-CONTENT-01'),
        title: CourseTitle::fromString('Contenido vial'),
        description: null,
        objectives: null,
        prerequisites: null,
        modality: null,
        durationHours: null,
        status: $status,
        publishedAt: $status === CourseStatus::Published ? new DateTimeImmutable('2026-08-05T12:00:00+00:00') : null,
        archivedAt: $status === CourseStatus::Archived ? new DateTimeImmutable('2026-08-05T12:00:00+00:00') : null,
        modules: [$module],
    );
}

/** @return list<LessonInput> */
function eng028LessonInputs(): array
{
    return [
        new LessonInput(
            id: '019c2c00-0000-7000-8000-000000001000',
            code: ' lesson-01 ',
            title: ' Introduccion segura ',
            summary: ' Panorama regional. ',
            durationMinutes: 15,
            position: 1,
            blocks: [
                new ContentBlockInput(
                    id: '019c2c00-0000-7000-8000-000000002000',
                    type: 'text',
                    position: 1,
                    payload: ['markdown' => ' Conduccion **segura**. ', 'title' => ' Apertura '],
                ),
                new ContentBlockInput(
                    id: '019c2c00-0000-7000-8000-000000002001',
                    type: 'download',
                    position: 2,
                    payload: [
                        'url' => ' https://cdn.edudrive.test/guias/seguridad.pdf ',
                        'display_name' => ' Guia accesible ',
                        'mime_type' => ' APPLICATION/PDF ',
                    ],
                ),
            ],
        ),
    ];
}

/** @return array<string, mixed> */
function eng028ExpectedContentResponse(string $status = 'draft'): array
{
    return [
        'course_id' => '019c2c00-0000-7000-8000-000000000001',
        'unit_id' => '019c2c00-0000-7000-8000-000000000100',
        'course_status' => $status,
        'lessons' => [[
            'id' => '019c2c00-0000-7000-8000-000000001000',
            'code' => 'LESSON-01',
            'title' => 'Introduccion segura',
            'summary' => 'Panorama regional.',
            'duration_minutes' => 15,
            'position' => 1,
            'blocks' => [
                [
                    'id' => '019c2c00-0000-7000-8000-000000002000',
                    'type' => 'text',
                    'position' => 1,
                    'payload' => ['markdown' => 'Conduccion **segura**.', 'title' => 'Apertura'],
                ],
                [
                    'id' => '019c2c00-0000-7000-8000-000000002001',
                    'type' => 'download',
                    'position' => 2,
                    'payload' => [
                        'url' => 'https://cdn.edudrive.test/guias/seguridad.pdf',
                        'display_name' => 'Guia accesible',
                        'mime_type' => 'application/pdf',
                    ],
                ],
            ],
        ]],
    ];
}

it('reemplaza una vez el contenido con bloques tipados y responde el agregado canonico', function (): void {
    $course = eng028ContentCourse();
    $courses = new Eng028ContentCourseRepository([$course]);
    $contents = new Eng028UnitContentRepository($courses);

    $response = (new ReplaceUnitContentHandler($courses, $contents))->handle(new ReplaceUnitContentCommand(
        courseId: $course->id()->value(),
        unitId: '019c2c00-0000-7000-8000-000000000100',
        lessons: eng028LessonInputs(),
    ));

    $stored = $contents->findForCourseUnit(
        $course->id(),
        CourseUnitId::fromString('019c2c00-0000-7000-8000-000000000100'),
    );

    expect($response->toArray())->toBe(eng028ExpectedContentResponse())
        ->and($contents->replaceCalls)->toBe(1)
        ->and($stored?->lessons()[0]->blocks()[0])->toBeInstanceOf(TextContentBlock::class)
        ->and($stored?->lessons()[0]->blocks()[1])->toBeInstanceOf(DownloadContentBlock::class);
});

it('consulta una unidad valida sin contenido como una lista vacia', function (): void {
    $course = eng028ContentCourse(CourseStatus::Published);
    $courses = new Eng028ContentCourseRepository([$course]);
    $contents = new Eng028UnitContentRepository($courses);

    $response = (new GetUnitContentHandler($courses, $contents))->handle(new GetUnitContentQuery(
        courseId: $course->id()->value(),
        unitId: '019c2c00-0000-7000-8000-000000000100',
    ));

    expect($response->toArray())->toBe([
        'course_id' => $course->id()->value(),
        'unit_id' => '019c2c00-0000-7000-8000-000000000100',
        'course_status' => 'published',
        'lessons' => [],
    ]);
});

it('rechaza consultar o reemplazar contenido de un curso inexistente', function (): void {
    $courses = new Eng028ContentCourseRepository;
    $contents = new Eng028UnitContentRepository($courses);
    $courseId = '019c2c00-0000-7000-8000-000000000999';
    $unitId = '019c2c00-0000-7000-8000-000000000100';

    foreach ([
        fn () => (new GetUnitContentHandler($courses, $contents))->handle(new GetUnitContentQuery($courseId, $unitId)),
        fn () => (new ReplaceUnitContentHandler($courses, $contents))->handle(new ReplaceUnitContentCommand($courseId, $unitId, eng028LessonInputs())),
    ] as $operation) {
        try {
            $operation();
            test()->fail('Se esperaba CourseNotFound.');
        } catch (CourseNotFound $exception) {
            expect($exception->errorCode())->toBe('COURSE_NOT_FOUND')
                ->and($exception->statusCode())->toBe(404);
        }
    }

    expect($contents->replaceCalls)->toBe(0);
});

it('oculta por igual una unidad inexistente y una unidad ajena', function (string $unitId): void {
    $course = eng028ContentCourse();
    $courses = new Eng028ContentCourseRepository([$course]);
    $contents = new Eng028UnitContentRepository($courses);

    foreach ([
        fn () => (new GetUnitContentHandler($courses, $contents))->handle(new GetUnitContentQuery($course->id()->value(), $unitId)),
        fn () => (new ReplaceUnitContentHandler($courses, $contents))->handle(new ReplaceUnitContentCommand($course->id()->value(), $unitId, eng028LessonInputs())),
    ] as $operation) {
        try {
            $operation();
            test()->fail('Se esperaba CourseUnitNotFound.');
        } catch (CourseUnitNotFound $exception) {
            expect($exception->errorCode())->toBe('COURSE_UNIT_NOT_FOUND')
                ->and($exception->statusCode())->toBe(404);
        }
    }

    expect($contents->replaceCalls)->toBe(0);
})->with([
    'inexistente' => '019c2c00-0000-7000-8000-000000000999',
    'ajena' => '019c2d00-0000-7000-8000-000000000100',
]);

it('rechaza reemplazar contenido de cursos no editables', function (CourseStatus $status): void {
    $course = eng028ContentCourse($status);
    $courses = new Eng028ContentCourseRepository([$course]);
    $contents = new Eng028UnitContentRepository($courses);

    expect(fn () => (new ReplaceUnitContentHandler($courses, $contents))->handle(new ReplaceUnitContentCommand(
        $course->id()->value(),
        '019c2c00-0000-7000-8000-000000000100',
        eng028LessonInputs(),
    )))->toThrow(CourseContentCannotBeModified::class);

    expect($contents->replaceCalls)->toBe(1)
        ->and($contents->findForCourseUnit(
            $course->id(),
            CourseUnitId::fromString('019c2c00-0000-7000-8000-000000000100'),
        )?->lessons())->toBe([]);
})->with([
    'published' => CourseStatus::Published,
    'archived' => CourseStatus::Archived,
]);

it('no persiste ni altera el contenido previo cuando el candidato es invalido', function (): void {
    $course = eng028ContentCourse();
    $courses = new Eng028ContentCourseRepository([$course]);
    $contents = new Eng028UnitContentRepository($courses);
    $handler = new ReplaceUnitContentHandler($courses, $contents);
    $handler->handle(new ReplaceUnitContentCommand(
        $course->id()->value(),
        '019c2c00-0000-7000-8000-000000000100',
        eng028LessonInputs(),
    ));

    $invalid = eng028LessonInputs();
    $invalid[0] = new LessonInput(
        id: $invalid[0]->id,
        code: $invalid[0]->code,
        title: $invalid[0]->title,
        summary: $invalid[0]->summary,
        durationMinutes: $invalid[0]->durationMinutes,
        position: $invalid[0]->position,
        blocks: [new ContentBlockInput(
            id: '019c2c00-0000-7000-8000-000000002099',
            type: 'image',
            position: 1,
            payload: ['url' => 'http://insecure.test/image.jpg', 'alt' => 'Riesgo'],
        )],
    );

    expect(fn () => $handler->handle(new ReplaceUnitContentCommand(
        $course->id()->value(),
        '019c2c00-0000-7000-8000-000000000100',
        $invalid,
    )))->toThrow(InvalidContentBlock::class);

    $stored = $contents->findForCourseUnit(
        $course->id(),
        CourseUnitId::fromString('019c2c00-0000-7000-8000-000000000100'),
    );

    expect($contents->replaceCalls)->toBe(1)
        ->and($stored?->lessons()[0]->blocks()[0]->type()->value)->toBe('text');
});

it('traduce a curso inexistente si desaparece entre preflight y reemplazo atomico', function (): void {
    $course = eng028ContentCourse();
    $courses = new Eng028ContentCourseRepository([$course]);
    $contents = new Eng028UnitContentRepository($courses);
    $contents->courseDisappearsDuringReplace = true;

    expect(fn () => (new ReplaceUnitContentHandler($courses, $contents))->handle(new ReplaceUnitContentCommand(
        $course->id()->value(),
        '019c2c00-0000-7000-8000-000000000100',
        eng028LessonInputs(),
    )))->toThrow(CourseNotFound::class, $course->id()->value());

    expect($contents->replaceCalls)->toBe(1);
});

it('despacha reemplazo y consulta mediante los buses registrados', function (): void {
    $course = eng028ContentCourse();
    $courses = new Eng028ContentCourseRepository([$course]);
    $contents = new Eng028UnitContentRepository($courses);
    app()->instance(CourseRepository::class, $courses);
    app()->instance(UnitContentRepository::class, $contents);

    $replace = app(CommandBus::class)->dispatch(new ReplaceUnitContentCommand(
        $course->id()->value(),
        '019c2c00-0000-7000-8000-000000000100',
        eng028LessonInputs(),
    ));
    $get = app(QueryBus::class)->ask(new GetUnitContentQuery(
        $course->id()->value(),
        '019c2c00-0000-7000-8000-000000000100',
    ));

    expect($replace)->toBeInstanceOf(UnitContentResponse::class)
        ->and($get)->toBeInstanceOf(UnitContentResponse::class)
        ->and($get->toArray())->toBe(eng028ExpectedContentResponse())
        ->and($contents->replaceCalls)->toBe(1);
});
