<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Academic\Application\Services\TheoryStudyRecommendationService;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Entities\AttemptQuestion;
use Modules\Academic\Domain\Entities\AttemptQuestionGrade;
use Modules\Academic\Domain\Entities\CompetencyGrade;
use Modules\Academic\Domain\Entities\ExamQuestion;
use Modules\Academic\Domain\Entities\Responses\SingleChoiceResponse;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Enums\ExamKind;
use Modules\Academic\Domain\Enums\QuestionType;
use Modules\Academic\Domain\ValueObjects\AttemptQuestionId;
use Modules\Academic\Domain\ValueObjects\CompetencyId;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;
use Modules\Academic\Domain\ValueObjects\ExamId;
use Modules\Academic\Domain\ValueObjects\LicenseCategory;
use Modules\Academic\Domain\ValueObjects\QuestionId;

it('no genera recomendaciones para examenes standard o intentos no submitted', function (): void {
    $competencyId = CompetencyId::fromString((string) Str::uuid());
    $questionId = QuestionId::fromString((string) Str::uuid());
    $question = AttemptQuestion::create(
        AttemptQuestionId::fromString((string) Str::uuid()),
        1,
        $questionId,
        $competencyId,
        10,
        'Prompt',
        QuestionType::SingleChoice,
        [],
        SingleChoiceResponse::fromArray(['type' => 'single_choice', 'optionId' => 'opt-a']),
    );

    $standardExam = Exam::create(
        ExamId::fromString((string) Str::uuid()),
        CourseId::fromString((string) Str::uuid()),
        'Standard',
        [ExamQuestion::create(1, $questionId, 10)],
    );
    $theoryExam = Exam::create(
        ExamId::fromString((string) Str::uuid()),
        CourseId::fromString((string) Str::uuid()),
        'Theory',
        [ExamQuestion::create(1, $questionId, 10)],
        kind: ExamKind::Theory,
        licenseCategory: LicenseCategory::fromString('B1'),
    );

    $submittedAttempt = ExamAttempt::restore(
        ExamAttemptId::fromString((string) Str::uuid()),
        $theoryExam->id(),
        'user-1',
        ExamAttemptStatus::Submitted,
        new DateTimeImmutable('now'),
        new DateTimeImmutable('now'),
        'Theory',
        null,
        60,
        false,
        ExamFeedbackMode::AfterSubmission,
        [$question],
        5,
        10,
        50,
        false,
        [new AttemptQuestionGrade($question->id(), $questionId, $competencyId, 5, 10, 50, false, true)],
        [new CompetencyGrade($competencyId, 5, 10, 50)],
    );
    $inProgressAttempt = ExamAttempt::restore(
        ExamAttemptId::fromString((string) Str::uuid()),
        $theoryExam->id(),
        'user-1',
        ExamAttemptStatus::InProgress,
        new DateTimeImmutable('now'),
        null,
        'Theory',
        null,
        60,
        false,
        ExamFeedbackMode::AfterSubmission,
        [$question],
        0,
        10,
        0,
        false,
    );

    $service = new TheoryStudyRecommendationService;

    expect($service->build($submittedAttempt, $standardExam))->toBe([])
        ->and($service->build($inProgressAttempt, $theoryExam))->toBe([]);
});
