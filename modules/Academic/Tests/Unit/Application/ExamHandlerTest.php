<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\CreateExamCommand;
use Modules\Academic\Application\Commands\DeleteExamCommand;
use Modules\Academic\Application\Commands\UpdateExamCommand;
use Modules\Academic\Application\Exceptions\CourseNotFound;
use Modules\Academic\Application\Exceptions\ExamNotFound;
use Modules\Academic\Application\Exceptions\InvalidTheoryExam;
use Modules\Academic\Application\Exceptions\QuestionNotFound;
use Modules\Academic\Application\Queries\GetExamQuery;
use Modules\Academic\Application\Queries\ListExamsQuery;
use Modules\Academic\Application\Responses\ExamListItemResponse;
use Modules\Academic\Application\Responses\ExamResponse;
use Modules\Academic\Application\UseCases\CreateExamHandler;
use Modules\Academic\Application\UseCases\DeleteExamHandler;
use Modules\Academic\Application\UseCases\GetExamHandler;
use Modules\Academic\Application\UseCases\ListExamsHandler;
use Modules\Academic\Application\UseCases\UpdateExamHandler;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Entities\QuestionOption;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Enums\QuestionSourceKind;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\LicenseCategory;
use Modules\Academic\Domain\ValueObjects\QuestionId;
use Modules\Academic\Domain\ValueObjects\QuestionOptionId;

final class InMemoryExamRepository implements ExamRepository
{
    /** @var array<string, Exam> */
    public array $exams = [];

    public int $saveCalls = 0;

    public function save(Exam $exam): void
    {
        $this->saveCalls++;
        $this->exams[$exam->id()->value()] = $exam;
    }

    public function findById(ExamId $id): ?Exam
    {
        return $this->exams[$id->value()] ?? null;
    }

    public function all(?CourseId $courseId = null): array
    {
        $all = array_values($this->exams);
        if ($courseId === null) {
            return $all;
        }

        return array_values(array_filter(
            $all,
            static fn (Exam $exam): bool => $exam->courseId()->equals($courseId),
        ));
    }

    public function delete(ExamId $id): void
    {
        unset($this->exams[$id->value()]);
    }
}

/** @return list<array{questionId: string, points: int}> */
function examQuestionPayloads(array $ids): array
{
    return array_map(static fn (string $id): array => ['questionId' => $id, 'points' => 1], $ids);
}

/** Persists a course and two questions for exam handler tests, returning [courseId, questionIds]. */
function persistedExamFixtures(): array
{
    $courseRepository = app(CourseRepository::class);
    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('EXM-'.strtoupper((string) Str::random(4))),
        title: CourseTitle::fromString('Curso de examen'),
    );
    $courseRepository->save($course);

    $questionRepository = app(QuestionRepository::class);
    $competencyId = CompetencyId::fromString(persistedQuestionCompetencyId());
    $questionIds = [];
    foreach (['opt-a', 'opt-b'] as $refId) {
        $question = Question::create(
            QuestionId::fromString((string) Str::uuid()),
            QuestionType::SingleChoice,
            $competencyId,
            '¿Pregunta '.$refId.'?',
            1,
            SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => $refId]),
            [
                QuestionOption::create($refId, QuestionOptionId::fromString((string) Str::uuid()), 1, 'A'),
                QuestionOption::create('opt-x', QuestionOptionId::fromString((string) Str::uuid()), 2, 'B'),
            ],
        );
        $questionRepository->save($question);
        $questionIds[] = $question->id()->value();
    }

    return [$course->id()->value(), $questionIds];
}

/** @return array{0: string, 1: list<string>} */
function persistedOfficialExamFixtures(array $categories = ['B1']): array
{
    $courseRepository = app(CourseRepository::class);
    $course = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('EXT-'.strtoupper((string) Str::random(4))),
        title: CourseTitle::fromString('Curso teorico oficial'),
    );
    $courseRepository->save($course);

    $questionRepository = app(QuestionRepository::class);
    $competencyId = CompetencyId::fromString(persistedQuestionCompetencyId());
    $questionIds = [];
    foreach (['opt-a', 'opt-b'] as $refId) {
        $question = Question::create(
            QuestionId::fromString((string) Str::uuid()),
            QuestionType::SingleChoice,
            $competencyId,
            '¿Pregunta oficial '.$refId.'?',
            1,
            SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => $refId]),
            [
                QuestionOption::create($refId, QuestionOptionId::fromString((string) Str::uuid()), 1, 'A'),
                QuestionOption::create('opt-x', QuestionOptionId::fromString((string) Str::uuid()), 2, 'B'),
            ],
            sourceKind: QuestionSourceKind::Official,
            licenseCategories: array_map(
                static fn (string $category): LicenseCategory => LicenseCategory::fromString($category),
                $categories,
            ),
        );
        $questionRepository->save($question);
        $questionIds[] = $question->id()->value();
    }

    return [$course->id()->value(), $questionIds];
}

it('crea un examen exitosamente', function (): void {
    [$courseId, $questionIds] = persistedExamFixtures();
    $repository = new InMemoryExamRepository;
    $handler = new CreateExamHandler($repository, app(CourseRepository::class), app(QuestionRepository::class));

    $response = $handler->handle(new CreateExamCommand(
        courseId: $courseId,
        title: 'Examen final',
        maxAttempts: 2,
        passingScore: 70,
        questions: examQuestionPayloads($questionIds),
    ));

    expect($response)->toBeInstanceOf(ExamResponse::class)
        ->and($repository->saveCalls)->toBe(1)
        ->and($response->title)->toBe('Examen final')
        ->and($response->questions)->toHaveCount(2);
});

it('rechaza crear un examen con curso inexistente', function (): void {
    $repository = new InMemoryExamRepository;
    $handler = new CreateExamHandler($repository, app(CourseRepository::class), app(QuestionRepository::class));

    expect(fn () => $handler->handle(new CreateExamCommand(
        courseId: (string) Str::uuid(),
        title: 'Examen',
        questions: examQuestionPayloads([(string) Str::uuid()]),
    )))->toThrow(CourseNotFound::class)
        ->and($repository->saveCalls)->toBe(0);
});

it('rechaza crear un examen con pregunta inexistente', function (): void {
    [$courseId] = persistedExamFixtures();
    $repository = new InMemoryExamRepository;
    $handler = new CreateExamHandler($repository, app(CourseRepository::class), app(QuestionRepository::class));

    expect(fn () => $handler->handle(new CreateExamCommand(
        courseId: $courseId,
        title: 'Examen',
        questions: examQuestionPayloads([(string) Str::uuid()]),
    )))->toThrow(QuestionNotFound::class)
        ->and($repository->saveCalls)->toBe(0);
});

it('rechaza crear un examen theory con preguntas custom', function (): void {
    [$courseId, $questionIds] = persistedExamFixtures();
    $repository = new InMemoryExamRepository;
    $handler = new CreateExamHandler($repository, app(CourseRepository::class), app(QuestionRepository::class));

    expect(fn () => $handler->handle(new CreateExamCommand(
        courseId: $courseId,
        title: 'Theory invalido',
        kind: 'theory',
        licenseCategory: 'B1',
        questions: examQuestionPayloads($questionIds),
    )))->toThrow(InvalidTheoryExam::class)
        ->and($repository->saveCalls)->toBe(0);
});

it('rechaza crear un examen theory con preguntas fuera de categoria', function (): void {
    [$courseId, $questionIds] = persistedOfficialExamFixtures(['A1']);
    $repository = new InMemoryExamRepository;
    $handler = new CreateExamHandler($repository, app(CourseRepository::class), app(QuestionRepository::class));

    expect(fn () => $handler->handle(new CreateExamCommand(
        courseId: $courseId,
        title: 'Theory fuera de categoria',
        kind: 'theory',
        licenseCategory: 'B1',
        questions: examQuestionPayloads($questionIds),
    )))->toThrow(InvalidTheoryExam::class)
        ->and($repository->saveCalls)->toBe(0);
});

it('permite crear un examen theory con preguntas oficiales de la categoria', function (): void {
    [$courseId, $questionIds] = persistedOfficialExamFixtures(['B1', 'A2B']);
    $repository = new InMemoryExamRepository;
    $handler = new CreateExamHandler($repository, app(CourseRepository::class), app(QuestionRepository::class));

    $response = $handler->handle(new CreateExamCommand(
        courseId: $courseId,
        title: 'Theory valido',
        kind: 'theory',
        licenseCategory: 'B1',
        questions: examQuestionPayloads($questionIds),
    ));

    expect($response->kind)->toBe('theory')
        ->and($response->licenseCategory)->toBe('B1');
});

it('obtiene y lista exámenes filtrados por curso', function (): void {
    [$courseId, $questionIds] = persistedExamFixtures();
    $repository = new InMemoryExamRepository;
    $create = new CreateExamHandler($repository, app(CourseRepository::class), app(QuestionRepository::class));
    $create->handle(new CreateExamCommand(courseId: $courseId, title: 'Uno', questions: examQuestionPayloads($questionIds)));
    $create->handle(new CreateExamCommand(courseId: $courseId, title: 'Dos', questions: examQuestionPayloads($questionIds)));

    $otherCourse = Course::create(
        id: CourseId::fromString((string) Str::uuid()),
        code: CourseCode::fromString('EXM-'.strtoupper((string) Str::random(4))),
        title: CourseTitle::fromString('Otro curso'),
    );
    app(CourseRepository::class)->save($otherCourse);
    $create->handle(new CreateExamCommand(courseId: $otherCourse->id()->value(), title: 'De otro curso', questions: examQuestionPayloads($questionIds)));

    $all = (new ListExamsHandler($repository))->handle(new ListExamsQuery);
    expect($all)->toHaveCount(3);

    $filtered = (new ListExamsHandler($repository))->handle(new ListExamsQuery($courseId));
    expect($filtered)->toHaveCount(2)
        ->and($filtered[0])->toBeInstanceOf(ExamListItemResponse::class);

    $exam = null;
    foreach ($repository->exams as $stored) {
        if ($stored->courseId()->value() === $courseId) {
            $exam = $stored;
            break;
        }
    }
    $detail = (new GetExamHandler($repository))->handle(new GetExamQuery($exam->id()->value()));
    expect($detail)->toBeInstanceOf(ExamResponse::class);
});

it('actualiza y elimina un examen', function (): void {
    [$courseId, $questionIds] = persistedExamFixtures();
    $repository = new InMemoryExamRepository;
    $create = new CreateExamHandler($repository, app(CourseRepository::class), app(QuestionRepository::class));
    $created = $create->handle(new CreateExamCommand(courseId: $courseId, title: 'Antes', questions: examQuestionPayloads($questionIds)));

    $updated = (new UpdateExamHandler($repository, app(QuestionRepository::class)))->handle(new UpdateExamCommand(
        examId: $created->id,
        title: 'Después',
        maxAttempts: 3,
        questions: examQuestionPayloads($questionIds),
    ));
    expect($updated->title)->toBe('Después')
        ->and($updated->maxAttempts)->toBe(3);

    (new DeleteExamHandler($repository))->handle(new DeleteExamCommand($created->id));
    expect($repository->findById(ExamId::fromString($created->id)))->toBeNull();

    expect(fn () => (new GetExamHandler($repository))->handle(new GetExamQuery($created->id)))
        ->toThrow(ExamNotFound::class);
});

it('rechaza actualizar un examen inexistente', function (): void {
    $repository = new InMemoryExamRepository;
    $handler = new UpdateExamHandler($repository, app(QuestionRepository::class));

    expect(fn () => $handler->handle(new UpdateExamCommand(
        examId: (string) Str::uuid(),
        title: 'No existe',
        questions: examQuestionPayloads([(string) Str::uuid()]),
    )))->toThrow(ExamNotFound::class);
});
