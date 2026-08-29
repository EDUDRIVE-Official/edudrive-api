<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\AiGovernance\Application\Commands\InvokeAiGatewayCommand;
use Modules\AiGovernance\Application\Responses\AiGatewayInvocationResponse;
use Modules\AiGovernance\Application\Services\AiGatewayClient;
use Modules\AiGovernance\Application\UseCases\InvokeAiGatewayHandler;
use Modules\AiGovernance\Domain\ValueObjects\AiGatewayRequest;
use Modules\AiGovernance\Domain\ValueObjects\AiGatewayResponse;

final class FakeAiGatewayClient implements AiGatewayClient
{
    public ?AiGatewayRequest $lastRequest = null;

    public function invoke(AiGatewayRequest $request): AiGatewayResponse
    {
        $this->lastRequest = $request;

        return new AiGatewayResponse(
            decisionId: (string) Str::uuid(),
            output: 'respuesta generada',
            reviewStatus: 'pending',
            tokensInput: 12,
            tokensOutput: 34,
            costAmount: 0.05,
            latencyMs: 120,
        );
    }
}

it('invoca el gateway de IA y devuelve la respuesta de la decision registrada', function (): void {
    $client = new FakeAiGatewayClient;
    $aiSystemId = (string) Str::uuid();
    $requestedByUserId = (string) Str::uuid();

    $response = (new InvokeAiGatewayHandler($client))->handle(new InvokeAiGatewayCommand(
        aiSystemId: $aiSystemId,
        requestedByUserId: $requestedByUserId,
        promptId: null,
        input: 'texto de entrada',
    ));

    expect($response)->toBeInstanceOf(AiGatewayInvocationResponse::class)
        ->and($response->output)->toBe('respuesta generada')
        ->and($response->reviewStatus)->toBe('pending')
        ->and($response->tokensInput)->toBe(12)
        ->and($client->lastRequest?->aiSystemId)->toBe($aiSystemId)
        ->and($client->lastRequest?->requestedByUserId)->toBe($requestedByUserId);
});
