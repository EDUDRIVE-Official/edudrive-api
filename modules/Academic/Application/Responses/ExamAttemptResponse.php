<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Entities\AttemptQuestion;

final readonly class ExamAttemptResponse
{
    /** @return callable(AttemptQuestion): array<string, mixed> */
    public static function questionMapper(bool $showFeedback): callable
    {
        return static function (AttemptQuestion $question) use ($showFeedback): array {
            $base = [
                'position' => $question->position(),
                'question_id' => $question->questionId()->value(),
                'type' => $question->type()->value,
                'points' => $question->points(),
                'prompt' => $question->prompt(),
                'options' => $question->options(),
                'user_response' => $question->userResponse()?->toArray(),
            ];
            if ($showFeedback) {
                $base['is_correct'] = $question->isCorrect();
                $base['correct_response'] = $question->correctResponse()->toArray();
                $base['explanation'] = $question->explanation();
            }

            return $base;
        };
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @param  list<array<string, string|int|bool>>|null  $gradingBreakdown
     * @param  list<array<string, string|int>>|null  $competencyResults
     * @param  list<array{competency_id: string, score: int, total_points: int, percentage: int, question_ids: list<string>}>|null  $studyRecommendations
     */
    private function __construct(
        public string $id,
        public string $examId,
        public string $userId,
        public string $status,
        public string $startedAt,
        public ?string $submittedAt,
        public string $title,
        public ?int $durationMinutes,
        public int $passingScore,
        public bool $shuffleQuestions,
        public string $feedbackMode,
        public array $questions,
        public int $score,
        public int $totalPoints,
        public int $percentage,
        public bool $passed,
        public ?array $gradingBreakdown,
        public ?array $competencyResults,
        public ?array $studyRecommendations,
    ) {}

    /**
     * @param  callable(AttemptQuestion): array<string, mixed>  $questionMapper
     * @param  list<StudyRecommendationResponse>|null  $studyRecommendations
     */
    public static function fromAttempt(
        ExamAttempt $attempt,
        callable $questionMapper,
        bool $includeGrading = false,
        ?array $studyRecommendations = null,
    ): self {
        return new self(
            $attempt->id()->value(),
            $attempt->examId()->value(),
            $attempt->userId(),
            $attempt->status()->value,
            $attempt->startedAt()->format(DATE_ATOM),
            $attempt->submittedAt()?->format(DATE_ATOM),
            $attempt->title(),
            $attempt->durationMinutes(),
            $attempt->passingScore(),
            $attempt->shuffleQuestions(),
            $attempt->feedbackMode()->value,
            array_map($questionMapper, $attempt->questions()),
            $attempt->score(),
            $attempt->totalPoints(),
            $attempt->percentage(),
            $attempt->passed(),
            $includeGrading ? array_map(
                static fn ($grade): array => $grade->toArray(),
                $attempt->questionBreakdown(),
            ) : null,
            $includeGrading ? array_map(
                static fn ($grade): array => $grade->toArray(),
                $attempt->competencyBreakdown(),
            ) : null,
            $studyRecommendations === null ? null : array_map(
                static fn (StudyRecommendationResponse $recommendation): array => $recommendation->toArray(),
                $studyRecommendations,
            ),
        );
    }

    /**
     * @return array{
     *     id: string,
     *     exam_id: string,
     *     user_id: string,
     *     status: string,
     *     started_at: string,
     *     submitted_at: string|null,
     *     title: string,
     *     duration_minutes: int|null,
     *     passing_score: int,
     *     shuffle_questions: bool,
     *     feedback_mode: string,
     *     questions: list<array<string, mixed>>,
     *     score: int,
     *     total_points: int,
     *     percentage: int,
     *     passed: bool,
     *     grading_breakdown?: list<array<string, string|int|bool>>,
     *     competency_results?: list<array<string, string|int>>,
     *     study_recommendations?: list<array{competency_id: string, score: int, total_points: int, percentage: int, question_ids: list<string>}>
     * }
     */
    public function toArray(): array
    {
        /** @var array{
         *     id: string,
         *     exam_id: string,
         *     user_id: string,
         *     status: string,
         *     started_at: string,
         *     submitted_at: string|null,
         *     title: string,
         *     duration_minutes: int|null,
         *     passing_score: int,
         *     shuffle_questions: bool,
         *     feedback_mode: string,
         *     questions: list<array<string, mixed>>,
         *     score: int,
         *     total_points: int,
         *     percentage: int,
         *     passed: bool,
         *     grading_breakdown?: list<array<string, string|int|bool>>,
         *     competency_results?: list<array<string, string|int>>,
         *     study_recommendations?: list<array{competency_id: string, score: int, total_points: int, percentage: int, question_ids: list<string>}>
         * } $payload
         */
        $payload = [
            'id' => $this->id,
            'exam_id' => $this->examId,
            'user_id' => $this->userId,
            'status' => $this->status,
            'started_at' => $this->startedAt,
            'submitted_at' => $this->submittedAt,
            'title' => $this->title,
            'duration_minutes' => $this->durationMinutes,
            'passing_score' => $this->passingScore,
            'shuffle_questions' => $this->shuffleQuestions,
            'feedback_mode' => $this->feedbackMode,
            'questions' => $this->questions,
            'score' => $this->score,
            'total_points' => $this->totalPoints,
            'percentage' => $this->percentage,
            'passed' => $this->passed,
        ];

        if ($this->gradingBreakdown !== null) {
            $payload['grading_breakdown'] = $this->gradingBreakdown;
            $payload['competency_results'] = $this->competencyResults ?? [];
        }

        if ($this->studyRecommendations !== null && $this->studyRecommendations !== []) {
            $payload['study_recommendations'] = $this->studyRecommendations;
        }

        return $payload;
    }
}
