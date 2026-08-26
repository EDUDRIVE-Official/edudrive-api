<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class SubmitDecisionPointsCommand implements Command
{
    /**
     * @param  list<array{id: string, road_context: string, risk_level: string, driver_reaction: string, occurred_at: string}>  $decisions
     */
    public function __construct(
        public string $sessionId,
        public string $simulatorId,
        public array $decisions,
    ) {}
}
