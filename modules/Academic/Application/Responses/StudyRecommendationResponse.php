<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

final readonly class StudyRecommendationResponse
{
    /** @param list<string> $questionIds */
    public function __construct(
        public string $competencyId,
        public int $score,
        public int $totalPoints,
        public int $percentage,
        public array $questionIds,
    ) {}

    /** @return array{competency_id: string, score: int, total_points: int, percentage: int, question_ids: list<string>} */
    public function toArray(): array
    {
        return [
            'competency_id' => $this->competencyId,
            'score' => $this->score,
            'total_points' => $this->totalPoints,
            'percentage' => $this->percentage,
            'question_ids' => $this->questionIds,
        ];
    }
}
