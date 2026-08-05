<?php

declare(strict_types=1);

use Modules\Academic\Application\Commands\ArchiveCourseCommand;
use Modules\Academic\Application\Commands\PublishCourseCommand;
use Modules\Academic\Application\Commands\ReplaceCourseCurriculumCommand;
use Modules\Academic\Application\DTO\CourseModuleInput;
use Modules\Academic\Application\DTO\CourseUnitInput;
use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Application\Queries\GetCourseCurriculumQuery;
use Modules\Academic\Application\Responses\CourseCurriculumResponse;
use Modules\Academic\Application\UseCases\ArchiveCourseHandler;
use Modules\Academic\Application\UseCases\GetCourseCurriculumHandler;
use Modules\Academic\Application\UseCases\PublishCourseHandler;
use Modules\Academic\Application\UseCases\ReplaceCourseCurriculumHandler;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Exceptions\CourseUnitContentRequired;
use Modules\Academic\Domain\Exceptions\InvalidCurriculumPosition;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;
use Modules\Academic\Domain\ValueObjects\UnitContentCoverage;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;

final class Eng027CurriculumCourseRepository implements CourseRepository
{
    public int $saveCalls = 0;

    public int $atomicUpdateCalls = 0;

    /** @var array<string, Course> */
    private array $courses = [];

    /** @var array<string, array<string, CourseUnitId>> */
    private array $completeUnitIds = [];

    /** @param list<Course> $courses */
    public function __construct(array $courses = [])
    {
        foreach ($courses as $course) {
            $this->courses[$course->id()->value()] = $course;
        }
    }

    public function save(Course $course): void
    {
        $this->saveCalls++;
        $this->courses[$course->id()->value()] = $course;
    }

    public function updateAtomically(CourseId $id, Closure $mutation): ?Course
    {
        $course = $this->findById($id);

        if ($course === null) {
            return null;
        }

        $this->atomicUpdateCalls++;
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

        $this->atomicUpdateCalls++;
        $candidate = clone $course;
        $mutation($candidate, UnitContentCoverage::fromUnitIds(array_values($this->completeUnitIds[$id->value()] ?? [])));
        $this->courses[$id->value()] = $candidate;

        return $candidate;
    }

    public function markAllUnitsComplete(CourseId $id): void
    {
        $course = $this->findById($id);
        $this->completeUnitIds[$id->value()] = [];

        foreach ($course?->modules() ?? [] as $module) {
            foreach ($module->units() as $unit) {
                $this->completeUnitIds[$id->value()][$unit->id()->value()] = $unit->id();
            }
        }
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

function eng027CurriculumCourse(): Course
{
    return Course::create(
        id: CourseId::fromString('019c2b00-0000-7000-8000-000000000001'),
        code: CourseCode::fromString('ROAD-SAFETY-01'),
        title: CourseTitle::fromString('Seguridad vial regional'),
    );
}

/** @return list<CourseModuleInput> */
function eng027CurriculumInputs(): array
{
    $firstModuleId = '019c2b00-0000-7000-8000-000000000010';
    $firstUnitId = '019c2b00-0000-7000-8000-000000000100';

    return [
        new CourseModuleInput(
            id: $firstModuleId,
            code: ' mod-01 ',
            title: ' Fundamentos ',
            description: ' Bases de seguridad vial. ',
            objectives: ' Reconocer riesgos. ',
            durationMinutes: 90,
            position: 1,
            prerequisiteModuleIds: [],
            units: [
                new CourseUnitInput(
                    id: $firstUnitId,
                    code: ' uni-01 ',
                    title: ' Percepcion del riesgo ',
                    description: ' Identificacion de peligros. ',
                    objectives: null,
                    durationMinutes: 30,
                    position: 1,
                    prerequisiteUnitIds: [],
                ),
            ],
        ),
        new CourseModuleInput(
            id: '019c2b00-0000-7000-8000-000000000020',
            code: 'MOD-02',
            title: 'Conduccion preventiva',
            description: 'Aplicacion de tecnicas preventivas.',
            objectives: null,
            durationMinutes: null,
            position: 2,
            prerequisiteModuleIds: [$firstModuleId],
            units: [
                new CourseUnitInput(
                    id: '019c2b00-0000-7000-8000-000000000200',
                    code: 'UNI-02',
                    title: 'Toma de decisiones',
                    description: 'Respuesta ante situaciones de riesgo.',
                    objectives: 'Elegir una maniobra segura.',
                    durationMinutes: 45,
                    position: 1,
                    prerequisiteUnitIds: [$firstUnitId],
                ),
            ],
        ),
    ];
}

/** @return array<string, mixed> */
function eng027ExpectedCurriculumResponse(): array
{
    return [
        'id' => '019c2b00-0000-7000-8000-000000000001',
        'code' => 'ROAD-SAFETY-01',
        'title' => 'Seguridad vial regional',
        'status' => 'draft',
        'modules' => [
            [
                'id' => '019c2b00-0000-7000-8000-000000000010',
                'code' => 'MOD-01',
                'title' => 'Fundamentos',
                'description' => 'Bases de seguridad vial.',
                'objectives' => 'Reconocer riesgos.',
                'duration_minutes' => 90,
                'position' => 1,
                'prerequisite_module_ids' => [],
                'units' => [
                    [
                        'id' => '019c2b00-0000-7000-8000-000000000100',
                        'code' => 'UNI-01',
                        'title' => 'Percepcion del riesgo',
                        'description' => 'Identificacion de peligros.',
                        'objectives' => null,
                        'duration_minutes' => 30,
                        'position' => 1,
                        'prerequisite_unit_ids' => [],
                    ],
                ],
            ],
            [
                'id' => '019c2b00-0000-7000-8000-000000000020',
                'code' => 'MOD-02',
                'title' => 'Conduccion preventiva',
                'description' => 'Aplicacion de tecnicas preventivas.',
                'objectives' => null,
                'duration_minutes' => null,
                'position' => 2,
                'prerequisite_module_ids' => ['019c2b00-0000-7000-8000-000000000010'],
                'units' => [
                    [
                        'id' => '019c2b00-0000-7000-8000-000000000200',
                        'code' => 'UNI-02',
                        'title' => 'Toma de decisiones',
                        'description' => 'Respuesta ante situaciones de riesgo.',
                        'objectives' => 'Elegir una maniobra segura.',
                        'duration_minutes' => 45,
                        'position' => 1,
                        'prerequisite_unit_ids' => ['019c2b00-0000-7000-8000-000000000100'],
                    ],
                ],
            ],
        ],
    ];
}

it('reemplaza el curriculo completo mediante una mutacion atomica', function (): void {
    $course = eng027CurriculumCourse();
    $courses = new Eng027CurriculumCourseRepository([$course]);
    $handler = new ReplaceCourseCurriculumHandler($courses);

    $response = $handler->handle(new ReplaceCourseCurriculumCommand(
        courseId: $course->id()->value(),
        modules: eng027CurriculumInputs(),
    ));

    expect($response->toArray())->toBe(eng027ExpectedCurriculumResponse())
        ->and($courses->saveCalls)->toBe(0)
        ->and($courses->atomicUpdateCalls)->toBe(1)
        ->and($courses->findById($course->id())?->modules())->toHaveCount(2);
});

it('publica y archiva cursos mediante mutaciones atomicas', function (): void {
    $publishable = eng027CurriculumCourse();
    $publishableCourses = new Eng027CurriculumCourseRepository([$publishable]);
    (new ReplaceCourseCurriculumHandler($publishableCourses))->handle(new ReplaceCourseCurriculumCommand(
        courseId: $publishable->id()->value(),
        modules: eng027CurriculumInputs(),
    ));

    expect(fn () => (new PublishCourseHandler($publishableCourses))->handle(
        new PublishCourseCommand($publishable->id()->value()),
    ))->toThrow(CourseUnitContentRequired::class);
    expect($publishableCourses->findById($publishable->id())?->status()->value)->toBe('draft');

    $publishableCourses->markAllUnitsComplete($publishable->id());
    $published = (new PublishCourseHandler($publishableCourses))->handle(new PublishCourseCommand($publishable->id()->value()));

    $archivable = Course::create(
        id: CourseId::fromString('019c2b00-0000-7000-8000-000000000002'),
        code: CourseCode::fromString('ROAD-SAFETY-02'),
        title: CourseTitle::fromString('Curso archivable'),
    );
    $archivableCourses = new Eng027CurriculumCourseRepository([$archivable]);
    $archived = (new ArchiveCourseHandler($archivableCourses))->handle(
        new ArchiveCourseCommand($archivable->id()->value()),
    );

    expect($published->toArray()['status'])->toBe('published')
        ->and($publishableCourses->atomicUpdateCalls)->toBe(3)
        ->and($publishableCourses->saveCalls)->toBe(0)
        ->and($archived->toArray()['status'])->toBe('archived')
        ->and($archivableCourses->atomicUpdateCalls)->toBe(1)
        ->and($archivableCourses->saveCalls)->toBe(0);
});

it('consulta el curriculo ordenado sin guardar el curso', function (): void {
    $course = eng027CurriculumCourse();
    $courses = new Eng027CurriculumCourseRepository([$course]);
    (new ReplaceCourseCurriculumHandler($courses))->handle(new ReplaceCourseCurriculumCommand(
        courseId: $course->id()->value(),
        modules: eng027CurriculumInputs(),
    ));
    $courses->saveCalls = 0;
    $courses->atomicUpdateCalls = 0;

    $response = (new GetCourseCurriculumHandler($courses))->handle(
        new GetCourseCurriculumQuery($course->id()->value()),
    );

    expect($response->toArray())->toBe(eng027ExpectedCurriculumResponse())
        ->and($courses->saveCalls)->toBe(0)
        ->and($courses->atomicUpdateCalls)->toBe(0);
});

it('rechaza reemplazar el curriculo de un curso inexistente', function (): void {
    $courseId = '019c2b00-0000-7000-8000-000000000999';
    $courses = new Eng027CurriculumCourseRepository;

    try {
        (new ReplaceCourseCurriculumHandler($courses))->handle(
            new ReplaceCourseCurriculumCommand($courseId, []),
        );

        test()->fail('Se esperaba CourseNotFound.');
    } catch (CourseNotFound $exception) {
        expect($exception->statusCode())->toBe(404)
            ->and($exception->errorCode())->toBe('COURSE_NOT_FOUND')
            ->and($exception->getMessage())->toContain($courseId);
    }

    expect($courses->saveCalls)->toBe(0);
    expect($courses->atomicUpdateCalls)->toBe(0);
});

it('rechaza consultar el curriculo de un curso inexistente', function (): void {
    $courseId = '019c2b00-0000-7000-8000-000000000999';
    $courses = new Eng027CurriculumCourseRepository;

    expect(fn () => (new GetCourseCurriculumHandler($courses))->handle(
        new GetCourseCurriculumQuery($courseId),
    ))->toThrow(CourseNotFound::class, $courseId);

    expect($courses->saveCalls)->toBe(0);
});

it('no guarda ni muta el agregado cuando el dominio rechaza la estructura candidata', function (): void {
    $course = eng027CurriculumCourse();
    $courses = new Eng027CurriculumCourseRepository([$course]);
    $invalidModule = eng027CurriculumInputs()[0];

    expect(fn () => (new ReplaceCourseCurriculumHandler($courses))->handle(
        new ReplaceCourseCurriculumCommand(
            courseId: $course->id()->value(),
            modules: [new CourseModuleInput(
                id: $invalidModule->id,
                code: $invalidModule->code,
                title: $invalidModule->title,
                description: $invalidModule->description,
                objectives: $invalidModule->objectives,
                durationMinutes: $invalidModule->durationMinutes,
                position: 2,
                prerequisiteModuleIds: $invalidModule->prerequisiteModuleIds,
                units: $invalidModule->units,
            )],
        ),
    ))->toThrow(InvalidCurriculumPosition::class);

    expect($courses->saveCalls)->toBe(0)
        ->and($courses->atomicUpdateCalls)->toBe(1)
        ->and($course->modules())->toBe([]);
});

it('despacha el reemplazo y la consulta mediante los buses registrados por el proveedor real', function (): void {
    $course = eng027CurriculumCourse();
    $courses = new Eng027CurriculumCourseRepository([$course]);
    app()->instance(CourseRepository::class, $courses);

    $replaceResponse = app(CommandBus::class)->dispatch(new ReplaceCourseCurriculumCommand(
        courseId: $course->id()->value(),
        modules: eng027CurriculumInputs(),
    ));
    $getResponse = app(QueryBus::class)->ask(new GetCourseCurriculumQuery($course->id()->value()));

    expect($replaceResponse)->toBeInstanceOf(CourseCurriculumResponse::class)
        ->and($getResponse)->toBeInstanceOf(CourseCurriculumResponse::class)
        ->and($getResponse->toArray())->toBe(eng027ExpectedCurriculumResponse())
        ->and($courses->saveCalls)->toBe(0)
        ->and($courses->atomicUpdateCalls)->toBe(1);
});
