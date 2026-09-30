<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Domain\Services;

use DateTimeImmutable;
use Modules\RoadPassport\Domain\Aggregates\RoadPassport;
use Modules\RoadPassport\Domain\Enums\EvidenceType;
use Modules\RoadPassport\Domain\ValueObjects\Evidence;

final class RoadPassportTrustCalculator
{
    private const BASE_WEIGHT = [
        EvidenceType::ExamPassed->value => 15,
        EvidenceType::CourseCompleted->value => 10,
        EvidenceType::LessonCompleted->value => 3,
        EvidenceType::GuidedPracticeObserved->value => 4,
        EvidenceType::SelfReportedPractice->value => 1,
        EvidenceType::StudentReflection->value => 2,
    ];

    private const FULL_WEIGHT_DAYS = 90;

    private const MIN_WEIGHT_DAYS = 365;

    private const MIN_DECAY_FACTOR = 0.2;

    public function calculate(RoadPassport $passport, DateTimeImmutable $now): int
    {
        $evidence = $passport->evidence();
        if ($evidence === []) {
            return 0;
        }

        $rawTotal = 0.0;
        foreach ($evidence as $item) {
            $rawTotal += $this->weightFor($item, $now);
        }

        $consistencyMultiplier = min(1.0, 0.5 + 0.1 * count($evidence));

        return (int) min(100, (int) round($rawTotal * $consistencyMultiplier));
    }

    private function weightFor(Evidence $evidence, DateTimeImmutable $now): float
    {
        $baseWeight = self::BASE_WEIGHT[$evidence->type->value];
        $ageInDays = ($now->getTimestamp() - $evidence->occurredAt->getTimestamp()) / 86400;

        return $baseWeight * $this->qualityFactorFor($evidence) * $this->decayFactorFor($ageInDays);
    }

    private function qualityFactorFor(Evidence $evidence): float
    {
        if ($evidence->type !== EvidenceType::GuidedPracticeObserved) {
            return 1.0;
        }

        $details = $evidence->details;
        $hasSafeEnvironment = ($details['safe_environment_confirmed'] ?? false) === true;
        $hasContext = is_string($details['practice_context'] ?? null) && trim($details['practice_context']) !== '';
        $hasObservation = is_string($details['observation'] ?? null) && mb_strlen(trim($details['observation'])) >= 10;

        return $hasSafeEnvironment && $hasContext && $hasObservation ? 1.0 : 0.5;
    }

    private function decayFactorFor(float $ageInDays): float
    {
        if ($ageInDays <= self::FULL_WEIGHT_DAYS) {
            return 1.0;
        }

        if ($ageInDays >= self::MIN_WEIGHT_DAYS) {
            return self::MIN_DECAY_FACTOR;
        }

        $range = self::MIN_WEIGHT_DAYS - self::FULL_WEIGHT_DAYS;
        $progress = ($ageInDays - self::FULL_WEIGHT_DAYS) / $range;

        return 1.0 - $progress * (1.0 - self::MIN_DECAY_FACTOR);
    }
}
