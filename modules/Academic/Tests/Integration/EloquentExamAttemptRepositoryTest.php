<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Entities\AttemptQuestion;
use Modules\Academic\Domain\Entities\AttemptQuestionGrade;
use Modules\Academic\Domain\Entities\CompetencyGrade;
use Modules\Academic\Domain\Entities\ExamQuestion;
use Modules\Academic\Domain\Entities\QuestionOption;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Exceptions\InvalidExamAttempt;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\AttemptQuestionId;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\GradingResult;
use Modules\Academic\Domain\ValueObjects\QuestionId;
use Modules\Academic\Domain\ValueObjects\QuestionOptionId;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\ExamAttemptModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\ExamAttemptQuestionModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentExamAttemptRepository;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories\EloquentExamRepository;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

function attemptRepoFixtures(): array
{
    $courseId = createDraftCourseForPublishing('EXA-'.strtoupper((string) Str::random(4)))->id()->value();
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

    $exam = Exam::create(
        ExamId::fromString((string) Str::uuid()),
        CourseId::fromString($courseId),
        'Examen integración intento',
        array_map(
            static fn (string $id, int $index): ExamQuestion => ExamQuestion::create($index + 1, QuestionId::fromString($id), 10),
            $questionIds,
            array_keys($questionIds),
        ),
    );
    app(EloquentExamRepository::class)->save($exam);

    $userId = (string) Str::uuid();
    app(UserRepository::class)->save(User::register(
        id: $userId,
        name: 'Estudiante',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    ));

    return [$exam, $questionIds, $userId];
}

/** @return list<AttemptQuestion> */
function attemptRepoQuestions(array $questionIds): array
{
    $correctOptions = ['opt-a', 'opt-b'];
    $competencyId = CompetencyId::fromString(persistedQuestionCompetencyId());

    return array_map(
        static fn (string $id, int $index): AttemptQuestion => AttemptQuestion::create(
            AttemptQuestionId::fromString((string) Str::uuid()),
            $index + 1,
            QuestionId::fromString($id),
            $competencyId,
            10,
            '¿Pregunta '.($index + 1).'?',
            QuestionType::SingleChoice,
            [],
            SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => $correctOptions[$index]]),
            'Explicación',
        ),
        $questionIds,
        array_keys($questionIds),
    );
}

function attemptRepoGradingResult(array $questions): GradingResult
{
    return new GradingResult(
        10,
        20,
        50,
        false,
        [
            new AttemptQuestionGrade(
                $questions[0]->id(),
                $questions[0]->questionId(),
                $questions[0]->competencyId(),
                10,
                10,
                100,
                true,
                true,
            ),
            new AttemptQuestionGrade(
                $questions[1]->id(),
                $questions[1]->questionId(),
                $questions[1]->competencyId(),
                0,
                10,
                0,
                false,
                false,
            ),
        ],
        [
            new CompetencyGrade(
                $questions[0]->competencyId(),
                10,
                20,
                50,
            ),
        ],
    );
}

it('guarda y reconstruye un intento con sus preguntas y respuestas', function (): void {
    [$exam, $questionIds, $userId] = attemptRepoFixtures();
    $repository = app(EloquentExamAttemptRepository::class);
    $expectedCompetencyId = CompetencyId::fromString(persistedQuestionCompetencyId());

    $questions = array_map(
        static fn (string $id, int $index): AttemptQuestion => AttemptQuestion::create(
            AttemptQuestionId::fromString((string) Str::uuid()),
            $index + 1,
            QuestionId::fromString($id),
            $expectedCompetencyId,
            10,
            '¿Pregunta '.($index + 1).'?',
            QuestionType::SingleChoice,
            [],
            SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => ['opt-a', 'opt-b'][$index]]),
            'Explicación',
        ),
        $questionIds,
        array_keys($questionIds),
    );

    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString((string) Str::uuid()),
        $exam->id(),
        $userId,
        $exam->title(),
        45,
        70,
        false,
        ExamFeedbackMode::AfterSubmission,
        $questions,
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );
    $attempt->answer(1, SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']), new DateTimeImmutable('2026-08-12 10:01:00'));
    $attempt->submit(new DateTimeImmutable('2026-08-12 10:10:00'), attemptRepoGradingResult($questions));
    $repository->save($attempt);

    $stored = $repository->findById($attempt->id());

    expect($stored)->not->toBeNull()
        ->and($stored?->status())->toBe(ExamAttemptStatus::Submitted)
        ->and($stored?->score())->toBe(10)
        ->and($stored?->totalPoints())->toBe(20)
        ->and($stored?->percentage())->toBe(50)
        ->and($stored?->passed())->toBeFalse()
        ->and($stored?->questions())->toHaveCount(2)
        ->and($stored?->questions()[0]->competencyId()->equals($expectedCompetencyId))->toBeTrue()
        ->and($stored?->questions()[0]->userResponse())->not->toBeNull()
        ->and($stored?->questions()[0]->isCorrect())->toBeTrue();

    expect(ExamAttemptQuestionModel::query()->where('attempt_id', $attempt->id()->value())->value('competency_id'))
        ->toBe($expectedCompetencyId->value());
});

it('guarda y rehidrata grading_breakdown y competency_results al persistir un intento gradado', function (): void {
    [$exam, $questionIds, $userId] = attemptRepoFixtures();
    $repository = app(EloquentExamAttemptRepository::class);
    $competencyId = CompetencyId::fromString(persistedQuestionCompetencyId());
    $questions = array_map(
        static fn (string $id, int $index): AttemptQuestion => AttemptQuestion::create(
            AttemptQuestionId::fromString((string) Str::uuid()),
            $index + 1,
            QuestionId::fromString($id),
            $competencyId,
            10,
            '¿Pregunta '.($index + 1).'?',
            QuestionType::SingleChoice,
            [],
            SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => ['opt-a', 'opt-b'][$index]]),
            'Explicación',
        ),
        $questionIds,
        array_keys($questionIds),
    );

    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString((string) Str::uuid()),
        $exam->id(),
        $userId,
        $exam->title(),
        45,
        70,
        false,
        ExamFeedbackMode::AfterSubmission,
        $questions,
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );

    $gradingResult = attemptRepoGradingResult($questions);
    $attempt->answer(1, SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']), new DateTimeImmutable('2026-08-12 10:01:00'));
    $attempt->submit(new DateTimeImmutable('2026-08-12 10:10:00'), $gradingResult);

    $repository->save($attempt);
    $storedModel = ExamAttemptModel::query()->findOrFail($attempt->id()->value());
    $stored = $repository->findById($attempt->id());

    expect($storedModel->grading_breakdown)->toBe([
        [
            'attempt_question_id' => $questions[0]->id()->value(),
            'question_id' => $questions[0]->questionId()->value(),
            'competency_id' => $competencyId->value(),
            'score' => 10,
            'total_points' => 10,
            'percentage' => 100,
            'is_correct' => true,
            'is_answered' => true,
        ],
        [
            'attempt_question_id' => $questions[1]->id()->value(),
            'question_id' => $questions[1]->questionId()->value(),
            'competency_id' => $competencyId->value(),
            'score' => 0,
            'total_points' => 10,
            'percentage' => 0,
            'is_correct' => false,
            'is_answered' => false,
        ],
    ])->and($storedModel->competency_results)->toBe([
        [
            'competency_id' => $competencyId->value(),
            'score' => 10,
            'total_points' => 20,
            'percentage' => 50,
        ],
    ])->and($stored)->not->toBeNull()
        ->and($stored?->questionBreakdown())->toHaveCount(2)
        ->and($stored?->questionBreakdown()[0]->toArray())->toBe($gradingResult->questionBreakdown()[0]->toArray())
        ->and($stored?->questionBreakdown()[1]->toArray())->toBe($gradingResult->questionBreakdown()[1]->toArray())
        ->and($stored?->competencyBreakdown())->toHaveCount(1)
        ->and($stored?->competencyBreakdown()[0]->toArray())->toBe($gradingResult->competencyBreakdown()[0]->toArray());
});

it('rehidrata competency id desde academic_questions cuando el snapshot persistido es null', function (): void {
    [$exam, $questionIds, $userId] = attemptRepoFixtures();
    $repository = app(EloquentExamAttemptRepository::class);
    $expectedCompetencyId = CompetencyId::fromString((string) DB::table('academic_questions')
        ->where('id', $questionIds[0])
        ->value('competency_id'));

    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString((string) Str::uuid()),
        $exam->id(),
        $userId,
        $exam->title(),
        45,
        70,
        false,
        ExamFeedbackMode::None,
        array_map(
            static fn (string $id, int $index): AttemptQuestion => AttemptQuestion::create(
                AttemptQuestionId::fromString((string) Str::uuid()),
                $index + 1,
                QuestionId::fromString($id),
                $expectedCompetencyId,
                10,
                '¿Pregunta '.($index + 1).'?',
                QuestionType::SingleChoice,
                [],
                SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => ['opt-a', 'opt-b'][$index]]),
                'Explicación',
            ),
            $questionIds,
            array_keys($questionIds),
        ),
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );
    $repository->save($attempt);

    ExamAttemptQuestionModel::query()
        ->where('attempt_id', $attempt->id()->value())
        ->update(['competency_id' => null]);

    $stored = $repository->findById($attempt->id());

    expect($stored)->not->toBeNull()
        ->and($stored?->questions())->toHaveCount(2)
        ->and($stored?->questions()[0]->competencyId()->equals($expectedCompetencyId))->toBeTrue()
        ->and($stored?->questions()[1]->competencyId()->equals($expectedCompetencyId))->toBeTrue();
});

it('rehidrata competency id para varias preguntas legacy sin hacer fallback por fila', function (): void {
    [$exam, $questionIds, $userId] = attemptRepoFixtures();
    $repository = app(EloquentExamAttemptRepository::class);
    $expectedCompetencyId = CompetencyId::fromString((string) DB::table('academic_questions')
        ->where('id', $questionIds[0])
        ->value('competency_id'));

    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString((string) Str::uuid()),
        $exam->id(),
        $userId,
        $exam->title(),
        45,
        70,
        false,
        ExamFeedbackMode::None,
        array_map(
            static fn (string $id, int $index): AttemptQuestion => AttemptQuestion::create(
                AttemptQuestionId::fromString((string) Str::uuid()),
                $index + 1,
                QuestionId::fromString($id),
                $expectedCompetencyId,
                10,
                '¿Pregunta '.($index + 1).'?',
                QuestionType::SingleChoice,
                [],
                SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => ['opt-a', 'opt-b'][$index]]),
                'Explicación',
            ),
            $questionIds,
            array_keys($questionIds),
        ),
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );
    $repository->save($attempt);

    ExamAttemptQuestionModel::query()
        ->where('attempt_id', $attempt->id()->value())
        ->update(['competency_id' => null]);

    DB::enableQueryLog();
    $stored = $repository->findById($attempt->id());
    $queries = DB::getQueryLog();
    DB::disableQueryLog();
    DB::flushQueryLog();

    $fallbackQueries = array_values(array_filter(
        $queries,
        static fn (array $query): bool => str_contains((string) ($query['query'] ?? ''), 'academic_questions')
            && str_contains((string) ($query['query'] ?? ''), 'competency_id'),
    ));

    expect($stored)->not->toBeNull()
        ->and($stored?->questions())->toHaveCount(2)
        ->and($stored?->questions()[0]->competencyId()->equals($expectedCompetencyId))->toBeTrue()
        ->and($stored?->questions()[1]->competencyId()->equals($expectedCompetencyId))->toBeTrue()
        ->and($fallbackQueries)->toHaveCount(1);
});

it('falla explícitamente cuando no puede resolver competency id ni desde snapshot ni desde academic_questions', function (): void {
    $repository = app(EloquentExamAttemptRepository::class);
    $question = new ExamAttemptQuestionModel;
    $question->forceFill([
        'id' => (string) Str::uuid(),
        'position' => 1,
        'question_id' => (string) Str::uuid(),
        'competency_id' => null,
        'points' => 10,
        'prompt' => '¿Pregunta 1?',
        'type' => QuestionType::SingleChoice->value,
        'options' => [],
        'correct_response' => ['type' => 'single_choice', 'optionId' => 'opt-a'],
        'user_response' => null,
        'explanation' => 'Explicación',
        'is_correct' => null,
        'answered_at' => null,
    ]);

    $method = new ReflectionMethod(EloquentExamAttemptRepository::class, 'toAttemptQuestion');
    $method->setAccessible(true);

    expect(fn () => $method->invoke($repository, $question, []))->toThrow(InvalidExamAttempt::class);
});

it('encuentra el intento activo y cuenta los completados para max_attempts', function (): void {
    [$exam, $questionIds, $userId] = attemptRepoFixtures();
    $repository = app(EloquentExamAttemptRepository::class);
    $firstQuestions = attemptRepoQuestions($questionIds);

    $first = ExamAttempt::start(
        ExamAttemptId::fromString((string) Str::uuid()),
        $exam->id(),
        $userId,
        $exam->title(),
        45,
        70,
        false,
        ExamFeedbackMode::None,
        $firstQuestions,
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );
    $first->submit(new DateTimeImmutable('2026-08-12 10:10:00'), attemptRepoGradingResult($firstQuestions));
    $repository->save($first);

    $second = ExamAttempt::start(
        ExamAttemptId::fromString((string) Str::uuid()),
        $exam->id(),
        $userId,
        $exam->title(),
        45,
        70,
        false,
        ExamFeedbackMode::None,
        attemptRepoQuestions($questionIds),
        new DateTimeImmutable('2026-08-12 11:00:00'),
    );
    $repository->save($second);

    expect($repository->findActiveFor($exam->id(), $userId))->not->toBeNull()
        ->and($repository->countCompletedFor($exam->id(), $userId))->toBe(1);
});

it('lista intentos filtrados por usuario y estado', function (): void {
    [$exam, $questionIds, $userId] = attemptRepoFixtures();
    $repository = app(EloquentExamAttemptRepository::class);

    $repository->save(ExamAttempt::start(
        ExamAttemptId::fromString((string) Str::uuid()),
        $exam->id(),
        $userId,
        $exam->title(),
        45,
        70,
        false,
        ExamFeedbackMode::None,
        attemptRepoQuestions($questionIds),
        new DateTimeImmutable('2026-08-12 10:00:00'),
    ));

    $all = $repository->all();
    expect($all)->toHaveCount(1);

    $filtered = $repository->all(examId: $exam->id(), userId: $userId);
    expect($filtered)->toHaveCount(1);
});

it('borra el intento y sus preguntas en cascada', function (): void {
    [$exam, $questionIds, $userId] = attemptRepoFixtures();
    $repository = app(EloquentExamAttemptRepository::class);
    $attempt = ExamAttempt::start(
        ExamAttemptId::fromString((string) Str::uuid()),
        $exam->id(),
        $userId,
        $exam->title(),
        45,
        70,
        false,
        ExamFeedbackMode::None,
        attemptRepoQuestions($questionIds),
        new DateTimeImmutable('2026-08-12 10:00:00'),
    );
    $repository->save($attempt);

    ExamAttemptModel::query()
        ->where('id', $attempt->id()->value())
        ->delete();

    expect($repository->findById($attempt->id()))->toBeNull()
        ->and(ExamAttemptQuestionModel::query()
            ->where('attempt_id', $attempt->id()->value())
            ->count())->toBe(0);
});
