<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Responses;

use Modules\AiGovernance\Domain\ValueObjects\AiGatewayResponse;

final readonly class AiGatewayInvocationResponse
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

    public static function fromGatewayResponse(AiGatewayResponse $response): self
    {
        return new self(
            decisionId: $response->decisionId,
            output: $response->output,
            reviewStatus: $response->reviewStatus,
            tokensInput: $response->tokensInput,
            tokensOutput: $response->tokensOutput,
            costAmount: $response->costAmount,
            latencyMs: $response->latencyMs,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'decision_id' => $this->decisionId,
            'output' => $this->output,
            'review_status' => $this->reviewStatus,
            'tokens_input' => $this->tokensInput,
            'tokens_output' => $this->tokensOutput,
            'cost_amount' => $this->costAmount,
            'latency_ms' => $this->latencyMs,
        ];
    }
}
