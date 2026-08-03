<?php

declare(strict_types=1);

use Modules\Academic\Application\Commands\ChangeProgramAudienceCommand;
use Modules\Academic\Application\Commands\ReplaceProgramCoursesCommand;
use Modules\Academic\Application\Exceptions\CourseNotFoundForProgram;
use Modules\Academic\Application\Exceptions\ProgramNotFound;
use Modules\Academic\Application\UseCases\ChangeProgramAudienceHandler;
use Modules\Academic\Application\UseCases\ReplaceProgramCoursesHandler;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\EducationalProgram;
use Modules\Academic\Domain\Enums\LicenseStage;
use Modules\Academic\Domain\Enums\ProgramContext;
use Modules\Academic\Domain\Enums\VehicleType;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\ProgramRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\ProgramAudience;
use Modules\Academic\Domain\ValueObjects\ProgramCode;
use Modules\Academic\Domain\ValueObjects\ProgramId;

final class Task4InMemoryProgramRepository implements ProgramRepository
{
    public int $saveCalls = 0;

    /** @var array<string, EducationalProgram> */
    private array $programs = [];

    public function __construct(?EducationalProgram $program = null)
    {
        if ($program !== null) {
            $this->programs[$program->id()->value()] = $program;
        }
    }

    public function save(EducationalProgram $program): void
    {
        $this->saveCalls++;
        $this->programs[$program->id()->value()] = $program;
    }

    public function findById(ProgramId $id): ?EducationalProgram
    {
        return $this->programs[$id->value()] ?? null;
    }

    public function findByCode(ProgramCode $code): ?EducationalProgram
    {
        foreach ($this->programs as $program) {
            if ($program->code()->equals($code)) {
                return $program;
            }
        }

        return null;
    }

    public function existsByCode(ProgramCode $code): bool
    {
        return $this->findByCode($code) !== null;
    }

    public function all(): array
    {
        return array_values($this->programs);
    }
}

final class Task4InMemoryCourseRepository implements CourseRepository
{
    /** @var array<string, Course> */
    private array $courses = [];

    /** @param list<Course> $courses */
    public function __construct(array $courses)
    {
        foreach ($courses as $course) {
            $this->courses[$course->id()->value()] = $course;
        }
    }

    public function save(Course $course): void
    {
        $this->courses[$course->id()->value()] = $course;
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

function task4Program(): EducationalProgram
{
    return EducationalProgram::create(
        id: ProgramId::fromString('019c2600-0000-7000-8000-000000000100'),
        code: ProgramCode::fromString('MOTO-LEARNER-01'),
        title: 'Programa inicial de motocicleta',
        description: 'Formacion regional para aprendices.',
        audience: ProgramAudience::fromValues(
            minAge: 16,
            maxAge: 18,
            licenseStages: [LicenseStage::Learner],
            contexts: [ProgramContext::General],
            vehicleTypes: [VehicleType::Motorcycle],
        ),
    );
}

function task4Course(string $id, string $code): Course
{
    return Course::create(
        id: CourseId::fromString($id),
        code: CourseCode::fromString($code),
        title: CourseTitle::fromString("Curso {$code}"),
    );
}

it('reemplaza y reordena cursos existentes con posiciones consecutivas', function (): void {
    $firstCourse = task4Course('019c2600-0000-7000-8000-000000000001', 'COURSE-01');
    $secondCourse = task4Course('019c2600-0000-7000-8000-000000000002', 'COURSE-02');
    $program = task4Program();
    $program->replaceCourses([$firstCourse->id(), $secondCourse->id()]);
    $programs = new Task4InMemoryProgramRepository($program);
    $handler = new ReplaceProgramCoursesHandler(
        $programs,
        new Task4InMemoryCourseRepository([$firstCourse, $secondCourse]),
    );

    $response = $handler->handle(new ReplaceProgramCoursesCommand(
        programId: $program->id()->value(),
        courseIds: [$secondCourse->id()->value(), $firstCourse->id()->value()],
    ));

    expect($response->toArray()['courses'])->toBe([
        ['course_id' => $secondCourse->id()->value(), 'position' => 1],
        ['course_id' => $firstCourse->id()->value(), 'position' => 2],
    ])->and($programs->saveCalls)->toBe(1);
});

it('rechaza un curso desconocido con el error publico esperado sin guardar ni mutar parcialmente', function (): void {
    $firstCourse = task4Course('019c2600-0000-7000-8000-000000000001', 'COURSE-01');
    $originalCourse = task4Course('019c2600-0000-7000-8000-000000000002', 'COURSE-02');
    $unknownCourseId = '019c2600-0000-7000-8000-000000000099';
    $program = task4Program();
    $program->replaceCourses([$originalCourse->id()]);
    $programs = new Task4InMemoryProgramRepository($program);
    $handler = new ReplaceProgramCoursesHandler(
        $programs,
        new Task4InMemoryCourseRepository([$firstCourse, $originalCourse]),
    );

    try {
        $handler->handle(new ReplaceProgramCoursesCommand(
            programId: $program->id()->value(),
            courseIds: [$firstCourse->id()->value(), $unknownCourseId],
        ));

        test()->fail('Se esperaba CourseNotFoundForProgram.');
    } catch (CourseNotFoundForProgram $exception) {
        expect($exception->statusCode())->toBe(404)
            ->and($exception->errorCode())->toBe('PROGRAM_COURSE_NOT_FOUND')
            ->and($exception->getMessage())->toContain($unknownCourseId);
    }

    expect($programs->saveCalls)->toBe(0)
        ->and(array_map(
            static fn ($course): string => $course->courseId()->value(),
            $program->courses(),
        ))->toBe([$originalCourse->id()->value()]);
});

it('delega al dominio el rechazo de cursos duplicados sin guardar ni mutar', function (): void {
    $course = task4Course('019c2600-0000-7000-8000-000000000001', 'COURSE-01');
    $program = task4Program();
    $programs = new Task4InMemoryProgramRepository($program);
    $handler = new ReplaceProgramCoursesHandler(
        $programs,
        new Task4InMemoryCourseRepository([$course]),
    );

    expect(fn () => $handler->handle(new ReplaceProgramCoursesCommand(
        programId: $program->id()->value(),
        courseIds: [$course->id()->value(), $course->id()->value()],
    )))->toThrow(InvalidArgumentException::class, 'Un curso no puede aparecer mas de una vez en el programa.');

    expect($programs->saveCalls)->toBe(0)
        ->and($program->courses())->toBe([]);
});

it('rechaza reemplazar cursos de un programa inexistente', function (): void {
    $programId = '019c2600-0000-7000-8000-000000000999';
    $handler = new ReplaceProgramCoursesHandler(
        new Task4InMemoryProgramRepository,
        new Task4InMemoryCourseRepository([]),
    );

    try {
        $handler->handle(new ReplaceProgramCoursesCommand($programId, []));

        test()->fail('Se esperaba ProgramNotFound.');
    } catch (ProgramNotFound $exception) {
        expect($exception->statusCode())->toBe(404)
            ->and($exception->errorCode())->toBe('PROGRAM_NOT_FOUND')
            ->and($exception->getMessage())->toContain($programId);
    }
});

it('cambia la audiencia regional del programa', function (): void {
    $program = task4Program();
    $programs = new Task4InMemoryProgramRepository($program);
    $handler = new ChangeProgramAudienceHandler($programs);

    $response = $handler->handle(new ChangeProgramAudienceCommand(
        programId: $program->id()->value(),
        minAge: 21,
        maxAge: null,
        licenseStages: ['licensed', 'professional'],
        contexts: ['corporate'],
        vehicleTypes: ['automobile'],
    ));

    expect($response->toArray()['audience'])->toBe([
        'min_age' => 21,
        'max_age' => null,
        'license_stages' => ['licensed', 'professional'],
        'contexts' => ['corporate'],
        'vehicle_types' => ['automobile'],
    ])->and($programs->saveCalls)->toBe(1);
});

it('rechaza cambiar la audiencia de un programa inexistente', function (): void {
    $programId = '019c2600-0000-7000-8000-000000000999';
    $handler = new ChangeProgramAudienceHandler(new Task4InMemoryProgramRepository);

    expect(fn () => $handler->handle(new ChangeProgramAudienceCommand(
        programId: $programId,
        minAge: null,
        maxAge: null,
        licenseStages: [],
        contexts: [],
        vehicleTypes: [],
    )))->toThrow(ProgramNotFound::class, $programId);
});
