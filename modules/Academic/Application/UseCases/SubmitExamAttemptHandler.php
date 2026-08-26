<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use DateTimeImmutable;
use Modules\Academic\Application\Commands\SubmitExamAttemptCommand;
use Modules\Academic\Application\Exceptions\ExamAttemptAlreadySubmitted;
use Modules\Academic\Application\Exceptions\ExamAttemptNotFound;
use Modules\Academic\Application\Responses\ExamAttemptResponse;
use Modules\Academic\Application\Services\TheoryStudyRecommendationService;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Enums\ExamAttemptStatus;
use Modules\Academic\Domain\Enums\ExamKind;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\Repositories\ExamAttemptRepository;
use Modules\Academic\Domain\Repositories\ExamRepository;
use Modules\Academic\Domain\Services\ExamAttemptGrader;
use Modules\Academic\Domain\ValueObjects\ExamAttemptId;
use Modules\Academic\Domain\ValueObjects\GradingPolicy;
use Modules\Learning\Application\DTO\LearningEventEntry;
use Modules\Learning\Application\Services\LearningEventRecorder;
use Modules\Learning\Domain\ValueObjects\LearningVerb;
use Modules\RoadPassport\Application\DTO\EvidenceEntry;
use Modules\RoadPassport\Application\Services\RoadPassportEvidenceRecorder;
use Modules\RoadPassport\Domain\Enums\EvidenceType;

final readonly class SubmitExamAttemptHandler
{
    public function __construct(
        private ExamAttemptRepository $attempts,
        private ?ExamAttemptGrader $grader = null,
        private ?ExamRepository $exams = null,
        private ?TheoryStudyRecommendationService $recommendations = null,
        private ?EnrollmentRepository $enrollments = null,
        private ?LearningEventRecorder $learningEvents = null,
        private ?RoadPassportEvidenceRecorder $evidenceRecorder = null,
    ) {}

    public function handle(SubmitExamAttemptCommand $command): ExamAttemptResponse
    {
        $attempt = $this->ownedAttempt($command->attemptId, $command->userId);
        if ($attempt->status() !== ExamAttemptStatus::InProgress) {
            throw ExamAttemptAlreadySubmitted::create();
        }

        $submittedAt = new DateTimeImmutable('now');

        if ($attempt->hasTimedOutAt($submittedAt)) {
            $attempt->submit($submittedAt);
            $this->attempts->save($attempt);

            return ExamAttemptResponse::fromAttempt($attempt, ExamAttemptResponse::questionMapper(false), false);
        }

        $exam = $this->examForAttempt($attempt);

        $gradingResult = ($this->grader ?? new ExamAttemptGrader)->grade(
            $attempt,
            $this->gradingPolicyFor($exam),
        );

        $attempt->submit($submittedAt, $gradingResult);

        $this->attempts->save($attempt);

        $this->recordLearningEvent($attempt, $exam);
        $this->recordEvidenceIfPassed($attempt, $exam);

        $studyRecommendations = $exam === null
            ? null
            : ($this->recommendations ?? new TheoryStudyRecommendationService)->build($attempt, $exam);

        return ExamAttemptResponse::fromAttempt(
            $attempt,
            ExamAttemptResponse::questionMapper(true),
            true,
            $studyRecommendations,
        );
    }

    private function ownedAttempt(string $attemptId, string $userId): ExamAttempt
    {
        $attempt = $this->attempts->findById(ExamAttemptId::fromString($attemptId));
        if ($attempt === null || $attempt->userId() !== $userId) {
            throw ExamAttemptNotFound::withId($attemptId);
        }

        return $attempt;
    }

    private function examForAttempt(ExamAttempt $attempt): ?Exam
    {
        return $this->exams?->findById($attempt->examId());
    }

    private function gradingPolicyFor(?Exam $exam): GradingPolicy
    {
        if ($exam !== null && $exam->kind() === ExamKind::Theory) {
            return new GradingPolicy(
                allowPartialCredit: $exam->allowPartialCredit(),
                applyPenalties: $exam->applyPenalties(),
            );
        }

        return new GradingPolicy(allowPartialCredit: true, applyPenalties: true);
    }

    private function recordLearningEvent(ExamAttempt $attempt, ?Exam $exam): void
    {
        if ($this->enrollments === null || $this->learningEvents === null || $exam === null) {
            return;
        }

        $enrollment = $this->enrollments->findActiveOrPendingFor($exam->courseId(), $attempt->userId());
        if ($enrollment === null) {
            return;
        }

        $this->learningEvents->record(new LearningEventEntry(
            enrollmentId: $enrollment->id()->value(),
            userId: $attempt->userId(),
            courseId: $exam->courseId()->value(),
            verb: LearningVerb::ExamAttemptSubmitted,
            subjectId: $attempt->id()->value(),
            evidence: [
                'score' => $attempt->score(),
                'total_points' => $attempt->totalPoints(),
                'percentage' => $attempt->percentage(),
                'passed' => $attempt->passed(),
            ],
        ));
    }

    private function recordEvidenceIfPassed(ExamAttempt $attempt, ?Exam $exam): void
    {
        if ($this->enrollments === null || $this->evidenceRecorder === null || $exam === null || ! $attempt->passed()) {
            return;
        }

        if ($this->enrollments->findActiveOrPendingFor($exam->courseId(), $attempt->userId()) === null) {
            return;
        }

        $this->evidenceRecorder->record(new EvidenceEntry(
            userId: $attempt->userId(),
            type: EvidenceType::ExamPassed,
            subjectId: $attempt->id()->value(),
            courseId: $exam->courseId()->value(),
            details: [
                'score' => $attempt->score(),
                'total_points' => $attempt->totalPoints(),
                'percentage' => $attempt->percentage(),
            ],
        ));
    }
}
