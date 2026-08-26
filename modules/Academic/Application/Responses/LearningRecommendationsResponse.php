<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

final readonly class LearningRecommendationsResponse
{
    /**
     * @param  list<StudyRecommendationResponse>  $weakCompetencies
     * @param  list<RetryableExamResponse>  $retryableExams
     */
    public function __construct(
        public string $enrollmentId,
        public ?string $nextLessonId,
        public array $weakCompetencies,
        public array $retryableExams,
    ) {}

    /**
     * @return array{
     *     enrollment_id: string,
     *     next_lesson_id: ?string,
     *     weak_competencies: list<array{competency_id: string, score: int, total_points: int, percentage: int, question_ids: list<string>}>,
     *     retryable_exams: list<array{exam_id: string, title: string, last_percentage: int, passing_score: int, attempts_used: int, max_attempts: int}>,
     * }
     */
    public function toArray(): array
    {
        return [
            'enrollment_id' => $this->enrollmentId,
            'next_lesson_id' => $this->nextLessonId,
            'weak_competencies' => array_map(
                static fn (StudyRecommendationResponse $recommendation): array => $recommendation->toArray(),
                $this->weakCompetencies,
            ),
            'retryable_exams' => array_map(
                static fn (RetryableExamResponse $exam): array => $exam->toArray(),
                $this->retryableExams,
            ),
        ];
    }
}
