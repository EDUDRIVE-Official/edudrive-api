<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Services;

use LogicException;
use Modules\Academic\Domain\Aggregates\ExamAttempt;
use Modules\Academic\Domain\Entities\AttemptQuestion;
use Modules\Academic\Domain\Entities\AttemptQuestionGrade;
use Modules\Academic\Domain\Entities\CompetencyGrade;
use Modules\Academic\Domain\Entities\Responses\MatchingResponse;
use Modules\Academic\Domain\Entities\Responses\MultiSelectResponse;
use Modules\Academic\Domain\Entities\Responses\OrderingResponse;
use Modules\Academic\Domain\ValueObjects\GradingPolicy;
use Modules\Academic\Domain\ValueObjects\GradingResult;

class ExamAttemptGrader
{
    public function grade(ExamAttempt $attempt, GradingPolicy $policy): GradingResult
    {
        $questionBreakdown = [];
        $competencyTotals = [];

        foreach ($attempt->questions() as $question) {
            $grade = $this->gradeQuestion($question, $policy);
            $questionBreakdown[] = $grade;

            $competencyKey = $question->competencyId()->value();
            if (! array_key_exists($competencyKey, $competencyTotals)) {
                $competencyTotals[$competencyKey] = [
                    'competency_id' => $question->competencyId(),
                    'score' => 0,
                    'total_points' => 0,
                ];
            }

            $competencyTotals[$competencyKey]['score'] += $grade->score();
            $competencyTotals[$competencyKey]['total_points'] += $grade->totalPoints();
        }

        $score = array_sum(array_map(
            static fn (AttemptQuestionGrade $grade): int => $grade->score(),
            $questionBreakdown,
        ));
        $totalPoints = array_sum(array_map(
            static fn (AttemptQuestionGrade $grade): int => $grade->totalPoints(),
            $questionBreakdown,
        ));

        $competencyBreakdown = array_map(
            fn (array $totals): CompetencyGrade => new CompetencyGrade(
                $totals['competency_id'],
                $totals['score'],
                $totals['total_points'],
                $this->calculatePercentage($totals['score'], $totals['total_points']),
            ),
            array_values($competencyTotals),
        );

        $percentage = $this->calculatePercentage($score, $totalPoints);

        return new GradingResult(
            $score,
            $totalPoints,
            $percentage,
            $percentage >= $attempt->passingScore(),
            $questionBreakdown,
            $competencyBreakdown,
        );
    }

    private function gradeQuestion(AttemptQuestion $question, GradingPolicy $policy): AttemptQuestionGrade
    {
        $score = $this->calculateQuestionScore($question, $policy);
        $totalPoints = $question->points();

        return new AttemptQuestionGrade(
            $question->id(),
            $question->questionId(),
            $question->competencyId(),
            $score,
            $totalPoints,
            $this->calculatePercentage($score, $totalPoints),
            $question->isCorrect() === true,
            $question->answered(),
        );
    }

    private function calculateQuestionScore(AttemptQuestion $question, GradingPolicy $policy): int
    {
        if ($question->isCorrect() === true) {
            return $question->points();
        }

        if (! $question->answered()) {
            return 0;
        }

        if (! $policy->allowPartialCredit()) {
            return 0;
        }

        return match ($question->type()->value) {
            'multi_select' => $this->gradeMultiSelect($question, $policy),
            'matching' => $this->gradeMatching($question),
            'ordering' => $this->gradeOrdering($question),
            default => 0,
        };
    }

    private function gradeMultiSelect(AttemptQuestion $question, GradingPolicy $policy): int
    {
        $correctResponse = $question->correctResponse();
        $userResponse = $question->userResponse();

        if (! $correctResponse instanceof MultiSelectResponse || ! $userResponse instanceof MultiSelectResponse) {
            return 0;
        }

        $correctOptionIds = array_values(array_unique($correctResponse->optionIds));
        $selectedOptionIds = array_values(array_unique($userResponse->optionIds));
        $correctSelections = count(array_intersect($selectedOptionIds, $correctOptionIds));

        $score = $this->proportionalScore($question->points(), $correctSelections, count($correctOptionIds));

        if (! $policy->applyPenalties()) {
            return $score;
        }

        $invalidSelections = count(array_diff($selectedOptionIds, $correctOptionIds));
        $penalty = $this->proportionalScore($question->points(), $invalidSelections, count($correctOptionIds));

        return max(0, $score - $penalty);
    }

    private function gradeMatching(AttemptQuestion $question): int
    {
        $correctResponse = $question->correctResponse();
        $userResponse = $question->userResponse();

        if (! $correctResponse instanceof MatchingResponse || ! $userResponse instanceof MatchingResponse) {
            return 0;
        }

        $correctPairs = [];
        foreach ($correctResponse->pairs as $pair) {
            $correctPairs[$pair['leftId']] = $pair['rightId'];
        }

        $seenPairs = [];
        $correctMatches = 0;
        foreach ($userResponse->pairs as $pair) {
            $leftId = $pair['leftId'];
            $pairKey = $leftId.':'.$pair['rightId'];
            if (array_key_exists($pairKey, $seenPairs)) {
                continue;
            }

            $seenPairs[$pairKey] = true;
            if (($correctPairs[$leftId] ?? null) === $pair['rightId']) {
                $correctMatches++;
            }
        }

        return $this->proportionalScore($question->points(), $correctMatches, count($correctPairs));
    }

    private function gradeOrdering(AttemptQuestion $question): int
    {
        $correctResponse = $question->correctResponse();
        $userResponse = $question->userResponse();

        if (! $correctResponse instanceof OrderingResponse || ! $userResponse instanceof OrderingResponse) {
            return 0;
        }

        $correctPositions = 0;
        foreach ($correctResponse->itemIds as $index => $itemId) {
            if (($userResponse->itemIds[$index] ?? null) === $itemId) {
                $correctPositions++;
            }
        }

        return $this->proportionalScore($question->points(), $correctPositions, count($correctResponse->itemIds));
    }

    private function proportionalScore(int $points, int $hits, int $total): int
    {
        if ($total < 1) {
            return 0;
        }

        return max(0, (int) floor($points * ($hits / $total)));
    }

    private function calculatePercentage(int $score, int $totalPoints): int
    {
        if ($totalPoints < 1) {
            throw new LogicException('Base grading requires total points greater than zero.');
        }

        return (int) round($score / $totalPoints * 100);
    }
}
