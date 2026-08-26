<?php

declare(strict_types=1);

namespace Modules\Simulation\Domain\ValueObjects;

use Modules\Simulation\Domain\Enums\PracticalResultOutcome;

final readonly class PracticalResult
{
    /**
     * @param  list<PracticalResultError>  $errors
     * @param  list<string>  $competenciesDemonstrated
     * @param  list<string>  $recommendations
     */
    public function __construct(
        public string $sessionId,
        public PracticalResultOutcome $outcome,
        public int $score,
        public int $totalPenaltyPoints,
        public array $errors,
        public array $competenciesDemonstrated,
        public array $recommendations,
    ) {}
}
