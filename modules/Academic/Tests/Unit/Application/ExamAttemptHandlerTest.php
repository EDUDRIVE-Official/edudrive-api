<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\AnswerAttemptQuestionCommand;
use Modules\Academic\Application\Commands\StartExamAttemptCommand;
use Modules\Academic\Application\Commands\SubmitExamAttemptCommand;
use Modules\Academic\Application\Exceptions\ExamAttemptAlreadySubmitted;
use Modules\Academic\Application\Exceptions\ExamAttemptLimitReached;
use Modules\Academic\Application\Exceptions\ExamAttemptNotFound;
use Modules\Academic\Application\Exceptions\ExamNotFound;
use Modules\Academic\Application\Queries\GetExamAttemptQuery;
use Modules\Academic\Application\Queries\ListExamAttemptsQuery;
use Modules\Academic\Application\Responses\ExamAttemptListItemResponse;
use Modules\Academic\Application\Responses\ExamAttemptResponse;
use Modules\Academic\Application\UseCases\AnswerAttemptQuestionHandler;
use Modules\Academic\Application\UseCases\GetExamAttemptHandler;
use Modules\Academic\Application\UseCases\ListExamAttemptsHandler;
use Modules\Academic\Application\UseCases\StartExamAttemptHandler;
use Modules\Academic\Application\UseCases\SubmitExamAttemptHandler;
use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Entities\ExamQuestion;
use Modules\Academic\Domain\Entities\QuestionOption;
use Modules\Academic\Domain\Entities\Responses\MultiSelectResponse;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\Enums\ExamKind;
use Modules\Academic\Domain\Enums\QuestionSourceKind;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\Services\ExamAttemptGrader;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\GradingPolicy;
use Modules\Academic\Domain\ValueObjects\GradingResult;
use Modules\Academic\Domain\ValueObjects\LicenseCategory;
use Modules\Academic\Domain\ValueObjects\QuestionId;
use Modules\Academic\Domain\ValueObjects\QuestionOptionId;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Learning\Application\DTO\LearningEventEntry;
use Modules\Learning\Application\Services\LearningEventRecorder;
use Modules\Learning\Domain\ValueObjects\LearningVerb;

final class SpyExamAttemptGrader extends ExamAttemptGrader
{
    public int $gradeCalls = 0;

    public ?GradingPolicy $lastPolicy = null;

    public function grade(ExamAttempt $attempt, GradingPolicy $policy): GradingResult
    {
        $this->gradeCalls++;
        $this->lastPolicy = $policy;

        return parent::grade($attempt, $policy);
    }
}

final class SpyLearningEventRecorderForAttempts implements LearningEventRecorder
{
    /** @var list<LearningEventEntry> */
    public array $recorded = [];

    public function record(LearningEventEntry $entry): void
    {
        $this->recorded[] = $entry;
    }
}

final class InMemoryExamAttemptRepository implements ExamAttemptRepository
{
    /** @var array<string, ExamAttempt> */
    public array $attempts = [];

    public function save(ExamAttempt $attempt): void
    {
        $this->attempts[$attempt->id()->value()] = $attempt;
    }

    public function findById(ExamAttemptId $id): ?ExamAttempt
    {
        return $this->attempts[$id->value()] ?? null;
    }

    public function findActiveFor(ExamId $examId, string $userId): ?ExamAttempt
    {
        foreach ($this->attempts as $attempt) {
            if ($attempt->examId()->equals($examId)
                && $attempt->userId() === $userId
                && $attempt->status() === ExamAttemptStatus::InProgress
            ) {
                return $attempt;
            }
        }

        return null;
    }

    public function countCompletedFor(ExamId $examId, string $userId): int
    {
        $count = 0;
        foreach ($this->attempts as $attempt) {
            if ($attempt->examId()->equals($examId)
                && $attempt->userId() === $userId
                && $attempt->status() !== ExamAttemptStatus::InProgress
            ) {
                $count++;
            }
        }

        return $count;
    }

    public function all(?ExamId $examId = null, ?string $userId = null, ?ExamAttemptStatus $status = null): array
    {
        return array_values(array_filter(
            $this->attempts,
            static fn (ExamAttempt $attempt): bool => ($examId === null || $attempt->examId()->equals($examId))
                && ($userId === null || $attempt->userId() === $userId)
                && ($status === null || $attempt->status() === $status),
        ));
    }
}

function persistedLearningEventUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante de prueba',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );

    app(UserRepository::class)->save($user);

    return $user->id();
}

/** Persists a course with two questions and an exam, returning [examId, questionIds]. */
function persistedAttemptExam(): array
{
    $courseId = createDraftCourseForPublishing('EXM-'.strtoupper((string) Str::random(4)))->id()->value();
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

    $examRepository = app(ExamRepository::class);
    $exam = Exam::create(
        ExamId::fromString((string) Str::uuid()),
        CourseId::fromString($courseId),
        'Examen del intento',
        array_map(
            static fn (string $id, int $index): ExamQuestion => ExamQuestion::create($index + 1, QuestionId::fromString($id), 10),
            $questionIds,
            array_keys($questionIds),
        ),
        maxAttempts: 2,
        passingScore: 70,
    );
    $examRepository->save($exam);

    return [$exam->id()->value(), $questionIds];
}

/** Persists an exam with one multi-select question to prove grading integration. */
function persistedPartialCreditAttemptExam(): array
{
    $courseId = createDraftCourseForPublishing('EXM-'.strtoupper((string) Str::random(4)))->id()->value();
    $questionRepository = app(QuestionRepository::class);
    $competencyId = CompetencyId::fromString(persistedQuestionCompetencyId());

    $question = Question::create(
        QuestionId::fromString((string) Str::uuid()),
        QuestionType::MultiSelect,
        $competencyId,
        'Selecciona dos opciones correctas',
        1,
        MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['opt-a', 'opt-b']]),
        [
            QuestionOption::create('opt-a', QuestionOptionId::fromString((string) Str::uuid()), 1, 'A'),
            QuestionOption::create('opt-b', QuestionOptionId::fromString((string) Str::uuid()), 2, 'B'),
            QuestionOption::create('opt-c', QuestionOptionId::fromString((string) Str::uuid()), 3, 'C'),
        ],
    );
    $questionRepository->save($question);

    $examRepository = app(ExamRepository::class);
    $exam = Exam::create(
        ExamId::fromString((string) Str::uuid()),
        CourseId::fromString($courseId),
        'Examen con parcial',
        [ExamQuestion::create(1, $question->id(), 10)],
        maxAttempts: 1,
        passingScore: 50,
    );
    $examRepository->save($exam);

    return [$exam->id()->value(), $question->id()->value()];
}

/** Persists a theory exam with one multi-select official question, returning [examId, questionId]. */
function persistedTheoryPartialCreditAttemptExam(bool $allowPartialCredit, bool $applyPenalties): array
{
    $courseId = createDraftCourseForPublishing('THT-'.strtoupper((string) Str::random(4)))->id()->value();
    $questionRepository = app(QuestionRepository::class);
    $competencyId = CompetencyId::fromString(persistedQuestionCompetencyId());

    $question = Question::create(
        QuestionId::fromString((string) Str::uuid()),
        QuestionType::MultiSelect,
        $competencyId,
        'Selecciona dos opciones correctas',
        1,
        MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['opt-a', 'opt-b']]),
        [
            QuestionOption::create('opt-a', QuestionOptionId::fromString((string) Str::uuid()), 1, 'A'),
            QuestionOption::create('opt-b', QuestionOptionId::fromString((string) Str::uuid()), 2, 'B'),
            QuestionOption::create('opt-c', QuestionOptionId::fromString((string) Str::uuid()), 3, 'C'),
        ],
        sourceKind: QuestionSourceKind::Official,
        licenseCategories: [LicenseCategory::fromString('B1')],
    );
    $questionRepository->save($question);

    $examRepository = app(ExamRepository::class);
    $exam = Exam::create(
        ExamId::fromString((string) Str::uuid()),
        CourseId::fromString($courseId),
        'Examen teorico con parcial',
        [ExamQuestion::create(1, $question->id(), 10)],
        maxAttempts: 1,
        passingScore: 50,
        kind: ExamKind::Theory,
        licenseCategory: LicenseCategory::fromString('B1'),
        allowPartialCredit: $allowPartialCredit,
        applyPenalties: $applyPenalties,
    );
    $examRepository->save($exam);

    return [$exam->id()->value(), $question->id()->value()];
}

it('inicia un intento con snapshot del examen', function (): void {
    [$examId] = persistedAttemptExam();
    $repository = new InMemoryExamAttemptRepository;
    $handler = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));

    $response = $handler->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1'));

    expect($response)->toBeInstanceOf(ExamAttemptResponse::class)
        ->and($response->status)->toBe('in_progress')
        ->and($response->questions)->toHaveCount(2)
        ->and($repository->attempts)->toHaveCount(1);
});

it('rechaza iniciar un intento sobre un examen inexistente', function (): void {
    $repository = new InMemoryExamAttemptRepository;
    $handler = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));

    expect(fn () => $handler->handle(new StartExamAttemptCommand(examId: (string) Str::uuid(), userId: 'user-1')))
        ->toThrow(ExamNotFound::class);
});

it('rechaza un segundo intento activo para el mismo examen y usuario', function (): void {
    [$examId] = persistedAttemptExam();
    $repository = new InMemoryExamAttemptRepository;
    $handler = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));
    $handler->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1'));

    expect(fn () => $handler->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1')))
        ->toThrow(ExamAttemptLimitReached::class);
});

it('rechaza iniciar un intento cuando se excede max_attempts', function (): void {
    [$examId] = persistedAttemptExam();
    $repository = new InMemoryExamAttemptRepository;
    $handler = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));
    $submit = new SubmitExamAttemptHandler($repository);

    $first = $handler->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1'));
    $submit->handle(new SubmitExamAttemptCommand($first->id, 'user-1'));
    $second = $handler->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1'));
    $submit->handle(new SubmitExamAttemptCommand($second->id, 'user-1'));

    expect(fn () => $handler->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1')))
        ->toThrow(ExamAttemptLimitReached::class);
});

it('responde una pregunta del intento', function (): void {
    [$examId] = persistedAttemptExam();
    $repository = new InMemoryExamAttemptRepository;
    $start = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));
    $created = $start->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1'));

    $answer = new AnswerAttemptQuestionHandler($repository);
    $response = $answer->handle(new AnswerAttemptQuestionCommand(
        attemptId: $created->id,
        userId: 'user-1',
        position: 1,
        response: SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
    ));

    expect($response->questions[0]['user_response'])->not->toBeNull();
});

it('rechaza responder un intento de otro usuario', function (): void {
    [$examId] = persistedAttemptExam();
    $repository = new InMemoryExamAttemptRepository;
    $start = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));
    $created = $start->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1'));

    $answer = new AnswerAttemptQuestionHandler($repository);
    expect(fn () => $answer->handle(new AnswerAttemptQuestionCommand(
        attemptId: $created->id,
        userId: 'user-2',
        position: 1,
        response: SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
    )))->toThrow(ExamAttemptNotFound::class);
});

it('envía el intento y calcula el resultado', function (): void {
    [$examId] = persistedAttemptExam();
    $repository = new InMemoryExamAttemptRepository;
    $start = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));
    $created = $start->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1'));
    $answer = new AnswerAttemptQuestionHandler($repository);
    $answer->handle(new AnswerAttemptQuestionCommand($created->id, 'user-1', 1, SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a'])));
    $answer->handle(new AnswerAttemptQuestionCommand($created->id, 'user-1', 2, SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-b'])));

    $response = (new SubmitExamAttemptHandler($repository))->handle(new SubmitExamAttemptCommand($created->id, 'user-1'));

    expect($response->status)->toBe('submitted')
        ->and($response->score)->toBe(20)
        ->and($response->percentage)->toBe(100)
        ->and($response->passed)->toBeTrue();
});

it('envía el intento usando el grader completo y conserva breakdowns en el agregado', function (): void {
    [$examId] = persistedPartialCreditAttemptExam();
    $repository = new InMemoryExamAttemptRepository;
    $start = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));
    $created = $start->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1'));
    $answer = new AnswerAttemptQuestionHandler($repository);
    $answer->handle(new AnswerAttemptQuestionCommand(
        $created->id,
        'user-1',
        1,
        MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['opt-a']]),
    ));

    $response = (new SubmitExamAttemptHandler($repository))->handle(new SubmitExamAttemptCommand($created->id, 'user-1'));
    $savedAttempt = $repository->findById(ExamAttemptId::fromString($created->id));

    expect($response->status)->toBe('submitted')
        ->and($response->score)->toBe(5)
        ->and($response->percentage)->toBe(50)
        ->and($response->passed)->toBeTrue()
        ->and($savedAttempt)->not->toBeNull()
        ->and($savedAttempt?->questionBreakdown())->toHaveCount(1)
        ->and($savedAttempt?->questionBreakdown()[0]->score())->toBe(5)
        ->and($savedAttempt?->questionBreakdown()[0]->isCorrect())->toBeFalse()
        ->and($savedAttempt?->competencyBreakdown())->toHaveCount(1)
        ->and($savedAttempt?->competencyBreakdown()[0]->score())->toBe(5);
});

it('usa la politica del examen theory al enviar el intento', function (): void {
    [$examId] = persistedTheoryPartialCreditAttemptExam(false, false);
    $repository = new InMemoryExamAttemptRepository;
    $grader = new SpyExamAttemptGrader;
    $start = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));
    $created = $start->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1'));
    $answer = new AnswerAttemptQuestionHandler($repository);
    $answer->handle(new AnswerAttemptQuestionCommand(
        $created->id,
        'user-1',
        1,
        MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['opt-a']]),
    ));

    $response = (new SubmitExamAttemptHandler($repository, $grader, app(ExamRepository::class)))->handle(
        new SubmitExamAttemptCommand($created->id, 'user-1'),
    );

    expect($grader->gradeCalls)->toBe(1)
        ->and($grader->lastPolicy)->not->toBeNull()
        ->and($grader->lastPolicy?->allowPartialCredit())->toBeFalse()
        ->and($grader->lastPolicy?->applyPenalties())->toBeFalse()
        ->and($response->score)->toBe(0);
});

it('mantiene la politica previa para examenes standard', function (): void {
    [$examId] = persistedPartialCreditAttemptExam();
    $repository = new InMemoryExamAttemptRepository;
    $grader = new SpyExamAttemptGrader;
    $start = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));
    $created = $start->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1'));
    $answer = new AnswerAttemptQuestionHandler($repository);
    $answer->handle(new AnswerAttemptQuestionCommand(
        $created->id,
        'user-1',
        1,
        MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['opt-a']]),
    ));

    $response = (new SubmitExamAttemptHandler($repository, $grader, app(ExamRepository::class)))->handle(
        new SubmitExamAttemptCommand($created->id, 'user-1'),
    );

    expect($grader->lastPolicy)->not->toBeNull()
        ->and($grader->lastPolicy?->allowPartialCredit())->toBeTrue()
        ->and($grader->lastPolicy?->applyPenalties())->toBeTrue()
        ->and($response->score)->toBe(5);
});

it('devuelve recomendaciones de estudio para intentos theory enviados', function (): void {
    [$examId] = persistedTheoryPartialCreditAttemptExam(true, false);
    $repository = new InMemoryExamAttemptRepository;
    $start = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));
    $created = $start->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1'));
    $answer = new AnswerAttemptQuestionHandler($repository);
    $answer->handle(new AnswerAttemptQuestionCommand(
        $created->id,
        'user-1',
        1,
        MultiSelectResponse::fromArray(['type' => 'multi_select', 'optionIds' => ['opt-a']]),
    ));

    $response = (new SubmitExamAttemptHandler($repository, null, app(ExamRepository::class)))->handle(
        new SubmitExamAttemptCommand($created->id, 'user-1'),
    );

    expect($response->studyRecommendations)->toHaveCount(1)
        ->and($response->studyRecommendations[0]['percentage'])->toBe(50)
        ->and($response->studyRecommendations[0]['question_ids'])->toHaveCount(1);
});

it('cancela por timeout antes de gradear y no devuelve feedback en el submit', function (): void {
    [$examId] = persistedAttemptExam();
    $repository = new InMemoryExamAttemptRepository;
    $grader = new SpyExamAttemptGrader;
    $start = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));
    $created = $start->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1'));

    $staleAttempt = $repository->findById(ExamAttemptId::fromString($created->id));
    expect($staleAttempt)->not->toBeNull();

    $repository->save(ExamAttempt::restore(
        $staleAttempt->id(),
        $staleAttempt->examId(),
        $staleAttempt->userId(),
        $staleAttempt->status(),
        new DateTimeImmutable('2000-01-01 00:00:00'),
        $staleAttempt->submittedAt(),
        $staleAttempt->title(),
        1,
        $staleAttempt->passingScore(),
        $staleAttempt->shuffleQuestions(),
        $staleAttempt->feedbackMode(),
        $staleAttempt->questions(),
        $staleAttempt->score(),
        $staleAttempt->totalPoints(),
        $staleAttempt->percentage(),
        $staleAttempt->passed(),
        $staleAttempt->questionBreakdown(),
        $staleAttempt->competencyBreakdown(),
    ));

    $response = (new SubmitExamAttemptHandler($repository, $grader))->handle(new SubmitExamAttemptCommand($created->id, 'user-1'));
    $savedAttempt = $repository->findById(ExamAttemptId::fromString($created->id));

    expect($response->status)->toBe('canceled')
        ->and($response->score)->toBe(0)
        ->and($grader->gradeCalls)->toBe(0)
        ->and($response->questions[0])->not->toHaveKey('is_correct')
        ->and($response->questions[0])->not->toHaveKey('correct_response')
        ->and($response->questions[0])->not->toHaveKey('explanation')
        ->and($savedAttempt)->not->toBeNull()
        ->and($savedAttempt?->status())->toBe(ExamAttemptStatus::Canceled)
        ->and($savedAttempt?->questionBreakdown())->toBe([])
        ->and($savedAttempt?->competencyBreakdown())->toBe([]);
});

it('rechaza reenviar un intento ya enviado', function (): void {
    [$examId] = persistedAttemptExam();
    $repository = new InMemoryExamAttemptRepository;
    $start = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));
    $created = $start->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1'));
    $submit = new SubmitExamAttemptHandler($repository);
    $submit->handle(new SubmitExamAttemptCommand($created->id, 'user-1'));

    expect(fn () => $submit->handle(new SubmitExamAttemptCommand($created->id, 'user-1')))
        ->toThrow(ExamAttemptAlreadySubmitted::class);
});

it('obtiene el detalle de un intento para su dueño y oculta a otros usuarios', function (): void {
    [$examId] = persistedAttemptExam();
    $repository = new InMemoryExamAttemptRepository;
    $start = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));
    $created = $start->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1'));

    $detail = (new GetExamAttemptHandler($repository))->handle(new GetExamAttemptQuery($created->id, 'user-1', false));
    expect($detail)->toBeInstanceOf(ExamAttemptResponse::class);

    expect(fn () => (new GetExamAttemptHandler($repository))->handle(new GetExamAttemptQuery($created->id, 'user-2', false)))
        ->toThrow(ExamAttemptNotFound::class);
});

it('lista intentos filtrados', function (): void {
    [$examId] = persistedAttemptExam();
    $repository = new InMemoryExamAttemptRepository;
    $start = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));
    $start->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-1'));
    $start->handle(new StartExamAttemptCommand(examId: $examId, userId: 'user-2'));

    $all = (new ListExamAttemptsHandler($repository))->handle(new ListExamAttemptsQuery);
    expect($all)->toHaveCount(2)
        ->and($all[0])->toBeInstanceOf(ExamAttemptListItemResponse::class);

    $filtered = (new ListExamAttemptsHandler($repository))->handle(new ListExamAttemptsQuery(userId: 'user-1'));
    expect($filtered)->toHaveCount(1);
});

it('registra un evento de aprendizaje al enviar un intento calificado', function (): void {
    [$examId, $questionIds] = persistedAttemptExam();
    $exam = app(ExamRepository::class)->findById(ExamId::fromString($examId));
    $userId = persistedLearningEventUserId();

    $enrollment = Enrollment::create(
        id: EnrollmentId::fromString((string) Str::uuid()),
        courseId: $exam->courseId(),
        userId: $userId,
        status: EnrollmentStatus::Active,
        source: EnrollmentSource::Individual,
    );
    app(EnrollmentRepository::class)->save($enrollment);

    $repository = new InMemoryExamAttemptRepository;
    $start = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));
    $started = $start->handle(new StartExamAttemptCommand($examId, $userId));

    $answer = new AnswerAttemptQuestionHandler($repository);
    $answer->handle(new AnswerAttemptQuestionCommand(
        $started->id,
        $userId,
        1,
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
    ));

    $recorder = new SpyLearningEventRecorderForAttempts;
    $submit = new SubmitExamAttemptHandler(
        $repository,
        null,
        app(ExamRepository::class),
        null,
        app(EnrollmentRepository::class),
        $recorder,
    );
    $submit->handle(new SubmitExamAttemptCommand($started->id, $userId));

    expect($recorder->recorded)->toHaveCount(1)
        ->and($recorder->recorded[0]->enrollmentId)->toBe($enrollment->id()->value())
        ->and($recorder->recorded[0]->userId)->toBe($userId)
        ->and($recorder->recorded[0]->courseId)->toBe($exam->courseId()->value())
        ->and($recorder->recorded[0]->verb)->toBe(LearningVerb::ExamAttemptSubmitted)
        ->and($recorder->recorded[0]->subjectId)->toBe($started->id)
        ->and($recorder->recorded[0]->evidence)->toHaveKeys(['score', 'total_points', 'percentage', 'passed']);
});

it('no falla ni registra un evento si no hay enrollment resoluble para el curso del examen', function (): void {
    [$examId, $questionIds] = persistedAttemptExam();
    $userId = (string) Str::uuid();

    $repository = new InMemoryExamAttemptRepository;
    $start = new StartExamAttemptHandler($repository, app(ExamRepository::class), app(QuestionRepository::class));
    $started = $start->handle(new StartExamAttemptCommand($examId, $userId));

    $answer = new AnswerAttemptQuestionHandler($repository);
    $answer->handle(new AnswerAttemptQuestionCommand(
        $started->id,
        $userId,
        1,
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
    ));

    $recorder = new SpyLearningEventRecorderForAttempts;
    $submit = new SubmitExamAttemptHandler(
        $repository,
        null,
        app(ExamRepository::class),
        null,
        app(EnrollmentRepository::class),
        $recorder,
    );
    $response = $submit->handle(new SubmitExamAttemptCommand($started->id, $userId));

    expect($response)->toBeInstanceOf(ExamAttemptResponse::class)
        ->and($recorder->recorded)->toBeEmpty();
});
