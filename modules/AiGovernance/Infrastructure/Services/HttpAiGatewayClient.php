<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Infrastructure\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\AiGovernance\Application\Exceptions\AiSystemNotFound;
use Modules\AiGovernance\Application\Exceptions\AiSystemNotUsable;
use Modules\AiGovernance\Application\Services\AiGatewayClient;
use Modules\AiGovernance\Domain\Entities\AiDecision;
use Modules\AiGovernance\Domain\Enums\AiSupervisionLevel;
use Modules\AiGovernance\Domain\Repositories\AiDecisionRepository;
use Modules\AiGovernance\Domain\Repositories\AiSystemRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiGatewayRequest;
use Modules\AiGovernance\Domain\ValueObjects\AiGatewayResponse;
use Modules\AiGovernance\Domain\ValueObjects\AiSystemId;

final readonly class HttpAiGatewayClient implements AiGatewayClient
{
    public function __construct(
        private AiSystemRepository $systems,
        private AiDecisionRepository $decisions,
    ) {}

    public function invoke(AiGatewayRequest $request): AiGatewayResponse
    {
        $aiSystemId = AiSystemId::fromString($request->aiSystemId);
        $system = $this->systems->findById($aiSystemId);
        if ($system === null) {
            throw AiSystemNotFound::withId($request->aiSystemId);
        }

        if (! $system->isUsable()) {
            throw AiSystemNotUsable::withId($request->aiSystemId);
        }

        $startedAt = microtime(true);
        $httpResponse = Http::withHeaders([
            'Authorization' => 'Bearer '.config('ai_governance.gateway_api_key'),
        ])
            ->timeout(30)
            ->post((string) config('ai_governance.gateway_endpoint'), [
                'input' => $request->input,
                'prompt_id' => $request->promptId,
            ]);
        $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);

        $output = (string) ($httpResponse->json('output') ?? $httpResponse->body());
        $tokensInput = $httpResponse->json('tokens_input');
        $tokensOutput = $httpResponse->json('tokens_output');
        $costAmount = $httpResponse->json('cost_amount');
        $confidenceLevel = $httpResponse->json('confidence_level');

        $requiresReview = $system->supervisionLevel()->value >= AiSupervisionLevel::Proposes->value;

        $decision = AiDecision::record(
            id: (string) Str::uuid(),
            aiSystemId: $aiSystemId->value(),
            requestedByUserId: $request->requestedByUserId,
            inputSummary: Str::limit($request->input, 1000),
            outputSummary: Str::limit($output, 1000),
            requiresReview: $requiresReview,
            confidenceLevel: $confidenceLevel === null ? null : (float) $confidenceLevel,
            tokensInput: $tokensInput === null ? null : (int) $tokensInput,
            tokensOutput: $tokensOutput === null ? null : (int) $tokensOutput,
            costAmount: $costAmount === null ? null : (float) $costAmount,
            latencyMs: $latencyMs,
        );

        $this->decisions->save($decision);

        return new AiGatewayResponse(
            decisionId: $decision->id(),
            output: $output,
            reviewStatus: $decision->reviewStatus()->value,
            tokensInput: $decision->tokensInput(),
            tokensOutput: $decision->tokensOutput(),
            costAmount: $decision->costAmount(),
            latencyMs: $decision->latencyMs(),
        );
    }
}
