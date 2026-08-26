<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Application\Queries\ListTheoryExamAttemptsQuery;
use Modules\Academic\Application\Responses\TheoryExamAttemptListItemResponse;
use Modules\Academic\Application\UseCases\ListTheoryExamAttemptsHandler;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Aggregates\Question;
use Modules\Academic\Domain\Entities\AttemptQuestion;
use Modules\Academic\Domain\Entities\ExamQuestion;
use Modules\Academic\Domain\Entities\Responses\TrueFalseResponse;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Enums\ExamKind;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Repositories\QuestionRepository;
use Modules\Academic\Domain\ValueObjects\AttemptQuestionId;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\LicenseCategory;
use Modules\Academic\Domain\ValueObjects\QuestionId;

final class TheoryHistoryAttemptRepository implements ExamAttemptRepository
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

it('lista solo intentos teoricos y puede filtrar por categoria y usuario', function (): void {
    $examRepository = app(ExamRepository::class);
    $attemptRepository = new TheoryHistoryAttemptRepository;
    $questionRepository = app(QuestionRepository::class);

    $theoryQuestion = Question::create(
        QuestionId::fromString((string) Str::uuid()),
        QuestionType::TrueFalse,
        CompetencyId::fromString(persistedQuestionCompetencyId()),
        'Pregunta theory',
        1,
        TrueFalseResponse::fromArray(['type' => 'true_false', 'correct' => true]),
        [],
    );
    $otherTheoryQuestion = Question::create(
        QuestionId::fromString((string) Str::uuid()),
        QuestionType::TrueFalse,
        CompetencyId::fromString(persistedQuestionCompetencyId()),
        'Pregunta theory A1',
        1,
        TrueFalseResponse::fromArray(['type' => 'true_false', 'correct' => true]),
        [],
    );
    $standardQuestion = Question::create(
        QuestionId::fromString((string) Str::uuid()),
        QuestionType::TrueFalse,
        CompetencyId::fromString(persistedQuestionCompetencyId()),
        'Pregunta standard',
        1,
        TrueFalseResponse::fromArray(['type' => 'true_false', 'correct' => true]),
        [],
    );
    $questionRepository->save($theoryQuestion);
    $questionRepository->save($otherTheoryQuestion);
    $questionRepository->save($standardQuestion);

    $theoryExam = Exam::create(
        ExamId::fromString((string) Str::uuid()),
        CourseId::fromString(createDraftCourseForPublishing('THL-'.strtoupper((string) Str::random(4)))->id()->value()),
        'Theory B1',
        [ExamQuestion::create(1, $theoryQuestion->id(), 10)],
        kind: ExamKind::Theory,
        licenseCategory: LicenseCategory::fromString('B1'),
    );
    $otherTheoryExam = Exam::create(
        ExamId::fromString((string) Str::uuid()),
        CourseId::fromString(createDraftCourseForPublishing('THL-'.strtoupper((string) Str::random(4)))->id()->value()),
        'Theory A1',
        [ExamQuestion::create(1, $otherTheoryQuestion->id(), 10)],
        kind: ExamKind::Theory,
        licenseCategory: LicenseCategory::fromString('A1'),
    );
    $standardExam = Exam::create(
        ExamId::fromString((string) Str::uuid()),
        CourseId::fromString(createDraftCourseForPublishing('STD-'.strtoupper((string) Str::random(4)))->id()->value()),
        'Standard',
        [ExamQuestion::create(1, $standardQuestion->id(), 10)],
    );
    $examRepository->save($theoryExam);
    $examRepository->save($otherTheoryExam);
    $examRepository->save($standardExam);

    $question = AttemptQuestion::create(
        AttemptQuestionId::fromString((string) Str::uuid()),
        1,
        QuestionId::fromString((string) Str::uuid()),
        CompetencyId::fromString((string) Str::uuid()),
        10,
        'Prompt',
        QuestionType::TrueFalse,
        [],
        TrueFalseResponse::fromArray(['type' => 'true_false', 'correct' => true]),
    );

    $attemptRepository->save(ExamAttempt::restore(
        ExamAttemptId::fromString((string) Str::uuid()),
        $theoryExam->id(),
        'user-1',
        ExamAttemptStatus::Submitted,
        new DateTimeImmutable('now'),
        new DateTimeImmutable('now'),
        'Theory B1',
        null,
        60,
        false,
        ExamFeedbackMode::AfterSubmission,
        [$question],
        5,
        10,
        50,
        false,
    ));
    $attemptRepository->save(ExamAttempt::restore(
        ExamAttemptId::fromString((string) Str::uuid()),
        $otherTheoryExam->id(),
        'user-2',
        ExamAttemptStatus::Submitted,
        new DateTimeImmutable('now'),
        new DateTimeImmutable('now'),
        'Theory A1',
        null,
        60,
        false,
        ExamFeedbackMode::AfterSubmission,
        [$question],
        8,
        10,
        80,
        true,
    ));
    $attemptRepository->save(ExamAttempt::restore(
        ExamAttemptId::fromString((string) Str::uuid()),
        $standardExam->id(),
        'user-1',
        ExamAttemptStatus::Submitted,
        new DateTimeImmutable('now'),
        new DateTimeImmutable('now'),
        'Standard',
        null,
        60,
        false,
        ExamFeedbackMode::AfterSubmission,
        [$question],
        10,
        10,
        100,
        true,
    ));

    $handler = new ListTheoryExamAttemptsHandler($attemptRepository, $examRepository);

    $all = $handler->handle(new ListTheoryExamAttemptsQuery(userId: 'user-1'));
    expect($all)->toHaveCount(1)
        ->and($all[0])->toBeInstanceOf(TheoryExamAttemptListItemResponse::class)
        ->and($all[0]->licenseCategory)->toBe('B1');

    $filtered = $handler->handle(new ListTheoryExamAttemptsQuery(userId: null, targetUserId: 'user-2', licenseCategory: 'A1'));
    expect($filtered)->toHaveCount(1)
        ->and($filtered[0]->userId)->toBe('user-2')
        ->and($filtered[0]->licenseCategory)->toBe('A1');
});
