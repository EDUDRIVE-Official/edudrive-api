<?php

declare(strict_types=1);

use Modules\Academic\Application\Commands\ArchiveProgramCommand;
use Modules\Academic\Application\Commands\PublishProgramCommand;
use Modules\Academic\Application\Exceptions\CourseNotFoundForProgram;
use Modules\Academic\Application\Exceptions\ProgramCourseNotPublished;
use Modules\Academic\Application\Exceptions\ProgramNotFound;
use Modules\Academic\Application\UseCases\ArchiveProgramHandler;
use Modules\Academic\Application\UseCases\PublishProgramHandler;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\EducationalProgram;
use Modules\Academic\Domain\Enums\CourseStatus;
use Modules\Academic\Domain\Enums\ProgramStatus;
use Modules\Academic\Domain\Exceptions\ArchivedProgramCannotBeModified;
use Modules\Academic\Domain\Exceptions\ProgramAlreadyArchived;
use Modules\Academic\Domain\Exceptions\ProgramAlreadyPublished;
use Modules\Academic\Domain\Exceptions\ProgramRequiresCourses;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\ProgramRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\ProgramAudience;
use Modules\Academic\Domain\ValueObjects\ProgramCode;
use Modules\Academic\Domain\ValueObjects\ProgramId;

final class Task5InMemoryProgramRepository implements ProgramRepository
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

final class Task5InMemoryCourseRepository implements CourseRepository
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

function task5Program(): EducationalProgram
{
    return EducationalProgram::create(
        id: ProgramId::fromString('019c2700-0000-7000-8000-000000000100'),
        code: ProgramCode::fromString('REGIONAL-ROAD-SAFETY'),
        title: 'Programa regional de seguridad vial',
        description: 'Trayecto educativo regional.',
        audience: ProgramAudience::fromValues(null, null, [], [], []),
    );
}

function task5Course(string $id, string $code, CourseStatus $status): Course
{
    $course = Course::create(
        id: CourseId::fromString($id),
        code: CourseCode::fromString($code),
        title: CourseTitle::fromString("Curso {$code}"),
    );

    if ($status === CourseStatus::Published) {
        $course->publish(new DateTimeImmutable('2026-08-03 08:00:00'));
    }

    if ($status === CourseStatus::Archived) {
        $course->archive(new DateTimeImmutable('2026-08-03 08:00:00'));
    }

    return $course;
}

it('publica un programa cuando todos sus cursos estan publicados y conserva el orden en la respuesta', function (): void {
    $firstCourse = task5Course('019c2700-0000-7000-8000-000000000001', 'COURSE-01', CourseStatus::Published);
    $secondCourse = task5Course('019c2700-0000-7000-8000-000000000002', 'COURSE-02', CourseStatus::Published);
    $program = task5Program();
    $program->replaceCourses([$secondCourse->id(), $firstCourse->id()]);
    $programs = new Task5InMemoryProgramRepository($program);
    $handler = new PublishProgramHandler(
        $programs,
        new Task5InMemoryCourseRepository([$firstCourse, $secondCourse]),
    );

    $response = $handler->handle(new PublishProgramCommand($program->id()->value()));
    $data = $response->toArray();

    expect($data['status'])->toBe('published')
        ->and($data['courses'])->toBe([
            ['course_id' => $secondCourse->id()->value(), 'position' => 1],
            ['course_id' => $firstCourse->id()->value(), 'position' => 2],
        ])
        ->and($data['published_at'])->not->toBeNull()
        ->and(DateTimeImmutable::createFromFormat(DATE_ATOM, $data['published_at']))->toBeInstanceOf(DateTimeImmutable::class)
        ->and($programs->saveCalls)->toBe(1);
});

it('rechaza publicar un programa vacio sin guardarlo', function (): void {
    $program = task5Program();
    $programs = new Task5InMemoryProgramRepository($program);
    $handler = new PublishProgramHandler($programs, new Task5InMemoryCourseRepository([]));

    expect(fn () => $handler->handle(new PublishProgramCommand($program->id()->value())))
        ->toThrow(ProgramRequiresCourses::class, 'El programa requiere al menos un curso para ser publicado.');

    expect($programs->saveCalls)->toBe(0)
        ->and($program->status())->toBe(ProgramStatus::Draft);
});

it('rechaza un curso no publicado sin guardar ni mutar el programa', function (CourseStatus $status): void {
    $course = task5Course('019c2700-0000-7000-8000-000000000001', 'COURSE-01', $status);
    $program = task5Program();
    $program->replaceCourses([$course->id()]);
    $programs = new Task5InMemoryProgramRepository($program);
    $handler = new PublishProgramHandler($programs, new Task5InMemoryCourseRepository([$course]));

    try {
        $handler->handle(new PublishProgramCommand($program->id()->value()));

        test()->fail('Se esperaba ProgramCourseNotPublished.');
    } catch (ProgramCourseNotPublished $exception) {
        expect($exception->statusCode())->toBe(422)
            ->and($exception->errorCode())->toBe('PROGRAM_COURSE_NOT_PUBLISHED')
            ->and($exception->getMessage())->toContain($course->id()->value());
    }

    expect($programs->saveCalls)->toBe(0)
        ->and($program->status())->toBe(ProgramStatus::Draft)
        ->and($program->publishedAt())->toBeNull();
})->with([
    'curso en borrador' => CourseStatus::Draft,
    'curso archivado' => CourseStatus::Archived,
]);

it('rechaza publicar cuando un curso referenciado ya no existe', function (): void {
    $missingCourseId = CourseId::fromString('019c2700-0000-7000-8000-000000000099');
    $program = task5Program();
    $program->replaceCourses([$missingCourseId]);
    $programs = new Task5InMemoryProgramRepository($program);
    $handler = new PublishProgramHandler($programs, new Task5InMemoryCourseRepository([]));

    try {
        $handler->handle(new PublishProgramCommand($program->id()->value()));

        test()->fail('Se esperaba CourseNotFoundForProgram.');
    } catch (CourseNotFoundForProgram $exception) {
        expect($exception->statusCode())->toBe(404)
            ->and($exception->errorCode())->toBe('PROGRAM_COURSE_NOT_FOUND')
            ->and($exception->getMessage())->toContain($missingCourseId->value());
    }

    expect($programs->saveCalls)->toBe(0)
        ->and($program->status())->toBe(ProgramStatus::Draft);
});

it('rechaza publicar dos veces sin volver a guardar', function (): void {
    $course = task5Course('019c2700-0000-7000-8000-000000000001', 'COURSE-01', CourseStatus::Published);
    $program = task5Program();
    $program->replaceCourses([$course->id()]);
    $program->publish(new DateTimeImmutable('2026-08-03 09:00:00'));
    $programs = new Task5InMemoryProgramRepository($program);
    $handler = new PublishProgramHandler($programs, new Task5InMemoryCourseRepository([$course]));

    try {
        $handler->handle(new PublishProgramCommand($program->id()->value()));

        test()->fail('Se esperaba ProgramAlreadyPublished.');
    } catch (ProgramAlreadyPublished $exception) {
        expect($exception->statusCode())->toBe(422)
            ->and($exception->errorCode())->toBe('PROGRAM_ALREADY_PUBLISHED');
    }

    expect($programs->saveCalls)->toBe(0);
});

it('prioriza el error de programa ya publicado aunque el curso haya desaparecido', function (): void {
    $courseId = CourseId::fromString('019c2700-0000-7000-8000-000000000001');
    $program = task5Program();
    $program->replaceCourses([$courseId]);
    $program->publish(new DateTimeImmutable('2026-08-03 09:00:00'));
    $programs = new Task5InMemoryProgramRepository($program);
    $handler = new PublishProgramHandler($programs, new Task5InMemoryCourseRepository([]));

    expect(fn () => $handler->handle(new PublishProgramCommand($program->id()->value())))
        ->toThrow(ProgramAlreadyPublished::class, 'El programa ya esta publicado.');

    expect($programs->saveCalls)->toBe(0);
});

it('prioriza la inmutabilidad de un programa archivado aunque el curso haya desaparecido', function (): void {
    $courseId = CourseId::fromString('019c2700-0000-7000-8000-000000000001');
    $program = task5Program();
    $program->replaceCourses([$courseId]);
    $program->archive(new DateTimeImmutable('2026-08-03 09:00:00'));
    $programs = new Task5InMemoryProgramRepository($program);
    $handler = new PublishProgramHandler($programs, new Task5InMemoryCourseRepository([]));

    expect(fn () => $handler->handle(new PublishProgramCommand($program->id()->value())))
        ->toThrow(ArchivedProgramCannotBeModified::class, 'Un programa archivado no puede ser modificado.');

    expect($programs->saveCalls)->toBe(0);
});

it('rechaza publicar un programa inexistente', function (): void {
    $programId = '019c2700-0000-7000-8000-000000000999';
    $handler = new PublishProgramHandler(
        new Task5InMemoryProgramRepository,
        new Task5InMemoryCourseRepository([]),
    );

    expect(fn () => $handler->handle(new PublishProgramCommand($programId)))
        ->toThrow(ProgramNotFound::class, $programId);
});

it('archiva un programa en borrador y registra la fecha', function (): void {
    $program = task5Program();
    $programs = new Task5InMemoryProgramRepository($program);
    $handler = new ArchiveProgramHandler($programs);

    $data = $handler->handle(new ArchiveProgramCommand($program->id()->value()))->toArray();

    expect($data['status'])->toBe('archived')
        ->and($data['archived_at'])->not->toBeNull()
        ->and(DateTimeImmutable::createFromFormat(DATE_ATOM, $data['archived_at']))->toBeInstanceOf(DateTimeImmutable::class)
        ->and($programs->saveCalls)->toBe(1);
});

it('archiva un programa publicado conservando su fecha de publicacion', function (): void {
    $program = task5Program();
    $program->replaceCourses([CourseId::fromString('019c2700-0000-7000-8000-000000000001')]);
    $program->publish(new DateTimeImmutable('2026-08-03 09:00:00'));
    $programs = new Task5InMemoryProgramRepository($program);
    $handler = new ArchiveProgramHandler($programs);

    $data = $handler->handle(new ArchiveProgramCommand($program->id()->value()))->toArray();

    expect($data['status'])->toBe('archived')
        ->and($data['published_at'])->toBe('2026-08-03T09:00:00+00:00')
        ->and($data['archived_at'])->not->toBeNull()
        ->and($programs->saveCalls)->toBe(1);
});

it('rechaza archivar dos veces sin volver a guardar', function (): void {
    $program = task5Program();
    $program->archive(new DateTimeImmutable('2026-08-03 10:00:00'));
    $programs = new Task5InMemoryProgramRepository($program);
    $handler = new ArchiveProgramHandler($programs);

    try {
        $handler->handle(new ArchiveProgramCommand($program->id()->value()));

        test()->fail('Se esperaba ProgramAlreadyArchived.');
    } catch (ProgramAlreadyArchived $exception) {
        expect($exception->statusCode())->toBe(422)
            ->and($exception->errorCode())->toBe('PROGRAM_ALREADY_ARCHIVED');
    }

    expect($programs->saveCalls)->toBe(0);
});

it('rechaza archivar un programa inexistente', function (): void {
    $programId = '019c2700-0000-7000-8000-000000000999';
    $handler = new ArchiveProgramHandler(new Task5InMemoryProgramRepository);

    expect(fn () => $handler->handle(new ArchiveProgramCommand($programId)))
        ->toThrow(ProgramNotFound::class, $programId);
});
