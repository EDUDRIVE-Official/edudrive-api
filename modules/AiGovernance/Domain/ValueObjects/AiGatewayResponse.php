<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\ValueObjects;

final readonly class AiGatewayResponse
{
    public function __construct(
        public string $decisionId,
        public string $output,
        public string $reviewStatus,
        public ?int $tokensInput,
        public ?int $tokensOutput,
        public ?float $costAmount,
        public ?int $latencyMs,
    ) {}
}
