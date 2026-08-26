<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\StartTheoryExamSimulationCommand;
use Modules\Academic\Application\Exceptions\InvalidTheoryExam;
use Modules\Academic\Application\Queries\GetTheoryExamQuery;
use Modules\Academic\Application\Queries\ListTheoryExamsQuery;
use Modules\Academic\Application\Responses\ExamAttemptResponse;
use Modules\Academic\Application\Responses\ExamResponse;
use Modules\Academic\Application\Responses\TheoryExamListItemResponse;
use Modules\Academic\Application\UseCases\GetTheoryExamHandler;
use Modules\Academic\Application\UseCases\ListTheoryExamsHandler;
use Modules\Academic\Application\UseCases\StartExamAttemptHandler;
use Modules\Academic\Application\UseCases\StartTheoryExamSimulationHandler;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Entities\ExamQuestion;
use Modules\Academic\Domain\Entities\QuestionOption;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\Enums\ExamKind;
use Modules\Academic\Domain\Enums\QuestionSourceKind;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\LicenseCategory;
use Modules\Academic\Domain\ValueObjects\QuestionId;
use Modules\Academic\Domain\ValueObjects\QuestionOptionId;

final class TheoryTestExamAttemptRepository implements ExamAttemptRepository
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

function persistedTheoryAndStandardExams(): array
{
    $courseId = createDraftCourseForPublishing('THU-'.strtoupper((string) Str::random(4)))->id()->value();
    $questionRepository = app(QuestionRepository::class);
    $competencyId = CompetencyId::fromString(persistedQuestionCompetencyId());

    $theoryQuestion = Question::create(
        QuestionId::fromString((string) Str::uuid()),
        QuestionType::SingleChoice,
        $competencyId,
        'Pregunta theory',
        1,
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
        [
            QuestionOption::create('opt-a', QuestionOptionId::fromString((string) Str::uuid()), 1, 'A'),
            QuestionOption::create('opt-b', QuestionOptionId::fromString((string) Str::uuid()), 2, 'B'),
        ],
        sourceKind: QuestionSourceKind::Official,
        licenseCategories: [LicenseCategory::fromString('B1')],
    );
    $standardQuestion = Question::create(
        QuestionId::fromString((string) Str::uuid()),
        QuestionType::SingleChoice,
        $competencyId,
        'Pregunta standard',
        1,
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
        [
            QuestionOption::create('opt-a', QuestionOptionId::fromString((string) Str::uuid()), 1, 'A'),
            QuestionOption::create('opt-b', QuestionOptionId::fromString((string) Str::uuid()), 2, 'B'),
        ],
    );
    $questionRepository->save($theoryQuestion);
    $questionRepository->save($standardQuestion);

    $examRepository = app(ExamRepository::class);
    $theoryExam = Exam::create(
        ExamId::fromString((string) Str::uuid()),
        CourseId::fromString($courseId),
        'Theory',
        [ExamQuestion::create(1, $theoryQuestion->id(), 10)],
        kind: ExamKind::Theory,
        licenseCategory: LicenseCategory::fromString('B1'),
    );
    $standardExam = Exam::create(
        ExamId::fromString((string) Str::uuid()),
        CourseId::fromString($courseId),
        'Standard',
        [ExamQuestion::create(1, $standardQuestion->id(), 10)],
    );
    $examRepository->save($theoryExam);
    $examRepository->save($standardExam);

    return [$theoryExam, $standardExam];
}

it('lista solo examenes theory en el handler dedicado', function (): void {
    persistedTheoryAndStandardExams();

    $result = (new ListTheoryExamsHandler(app(ExamRepository::class)))->handle(new ListTheoryExamsQuery);

    expect($result)->toHaveCount(1)
        ->and($result[0])->toBeInstanceOf(TheoryExamListItemResponse::class)
        ->and($result[0]->kind)->toBe('theory');
});

it('obtiene detalle de un examen theory y rechaza standard', function (): void {
    [$theoryExam, $standardExam] = persistedTheoryAndStandardExams();
    $handler = new GetTheoryExamHandler(app(ExamRepository::class), app(QuestionRepository::class));

    $response = $handler->handle(new GetTheoryExamQuery($theoryExam->id()->value()));

    expect($response)->toBeInstanceOf(ExamResponse::class)
        ->and($response->kind)->toBe('theory');

    expect(fn () => $handler->handle(new GetTheoryExamQuery($standardExam->id()->value())))
        ->toThrow(InvalidTheoryExam::class);
});

it('inicia simulacion solo si el examen es theory', function (): void {
    [$theoryExam, $standardExam] = persistedTheoryAndStandardExams();
    $attempts = new TheoryTestExamAttemptRepository;
    $startAttempts = new StartExamAttemptHandler($attempts, app(ExamRepository::class), app(QuestionRepository::class));
    $handler = new StartTheoryExamSimulationHandler(app(ExamRepository::class), $startAttempts);

    $response = $handler->handle(new StartTheoryExamSimulationCommand($theoryExam->id()->value(), 'user-1'));

    expect($response)->toBeInstanceOf(ExamAttemptResponse::class)
        ->and($response->examId)->toBe($theoryExam->id()->value())
        ->and($response->userId)->toBe('user-1')
        ->and($response->status)->toBe(ExamAttemptStatus::InProgress->value)
        ->and($attempts->attempts)->toHaveCount(1);

    expect(fn () => $handler->handle(new StartTheoryExamSimulationCommand($standardExam->id()->value(), 'user-1')))
        ->toThrow(InvalidTheoryExam::class);
});
