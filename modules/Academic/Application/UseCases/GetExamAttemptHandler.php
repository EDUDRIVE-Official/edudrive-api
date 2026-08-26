<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Exceptions\ExamAttemptNotFound;
use Modules\Academic\Application\Queries\GetExamAttemptQuery;
use Modules\Academic\Application\Responses\ExamAttemptResponse;
use Modules\Academic\Application\Services\TheoryStudyRecommendationService;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\Enums\ExamFeedbackMode;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;

final readonly class GetExamAttemptHandler
{
    public function __construct(
        private ExamAttemptRepository $attempts,
        private ?ExamRepository $exams = null,
        private ?TheoryStudyRecommendationService $recommendations = null,
    ) {}

    public function handle(GetExamAttemptQuery $query): ExamAttemptResponse
    {
        $attempt = $this->attempts->findById(ExamAttemptId::fromString($query->attemptId));
        if ($attempt === null) {
            throw ExamAttemptNotFound::withId($query->attemptId);
        }

        $isOwner = $attempt->userId() === $query->userId;
        if (! $isOwner && ! $query->canViewOthers) {
            throw ExamAttemptNotFound::withId($query->attemptId);
        }

        $showFeedback = $attempt->status() === ExamAttemptStatus::Submitted
            && ($query->canViewOthers || $attempt->feedbackMode() !== ExamFeedbackMode::None);
        $showGrading = $showFeedback;

        $studyRecommendations = null;
        if ($showGrading) {
            $exam = $this->exams?->findById($attempt->examId());
            if ($exam !== null) {
                $studyRecommendations = ($this->recommendations ?? new TheoryStudyRecommendationService)->build($attempt, $exam);
            }
        }

        return ExamAttemptResponse::fromAttempt(
            $attempt,
            ExamAttemptResponse::questionMapper($showFeedback),
            $showGrading,
            $studyRecommendations,
        );
    }
}
