<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Services;

use Modules\Academic\Application\Responses\StudyRecommendationResponse;
use Modules\Academic\Domain\Aggregates\Exam;
use Modules\Academic\Domain\Aggregates\ExamAttempt;

final class TheoryStudyRecommendationService
{
    /** @return list<StudyRecommendationResponse> */
    public function build(ExamAttempt $attempt, Exam $exam): array
    {
        if ($exam->kind()->value !== 'theory' || $attempt->status()->value !== 'submitted') {
            return [];
        }

        $questionIdsByCompetency = [];
        foreach ($attempt->questionBreakdown() as $grade) {
            if ($grade->percentage() >= 100) {
                continue;
            }

            $competencyId = $grade->competencyId()->value();
            $questionIdsByCompetency[$competencyId] ??= [];
            $questionIdsByCompetency[$competencyId][] = $grade->questionId()->value();
        }

        $recommendations = [];
        foreach ($attempt->competencyBreakdown() as $grade) {
            $competencyId = $grade->competencyId()->value();
            $questionIds = array_values(array_unique($questionIdsByCompetency[$competencyId] ?? []));
            if ($questionIds === []) {
                continue;
            }

            $recommendations[] = new StudyRecommendationResponse(
                $competencyId,
                $grade->score(),
                $grade->totalPoints(),
                $grade->percentage(),
                $questionIds,
            );
        }

        usort($recommendations, static function (StudyRecommendationResponse $left, StudyRecommendationResponse $right): int {
            return [$left->percentage, $left->score, $left->competencyId] <=> [$right->percentage, $right->score, $right->competencyId];
        });

        return $recommendations;
    }
}
