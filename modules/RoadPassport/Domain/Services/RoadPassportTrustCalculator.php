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

        return $baseWeight * $this->decayFactorFor($ageInDays);
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
