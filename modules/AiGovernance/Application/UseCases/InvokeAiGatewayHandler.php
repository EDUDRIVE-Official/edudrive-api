<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use Modules\AiGovernance\Application\Commands\InvokeAiGatewayCommand;
use Modules\AiGovernance\Application\Responses\AiGatewayInvocationResponse;
use Modules\AiGovernance\Application\Services\AiGatewayClient;
use Modules\AiGovernance\Domain\ValueObjects\AiGatewayRequest;

final readonly class InvokeAiGatewayHandler
{
    public function __construct(private AiGatewayClient $gateway) {}

    public function handle(InvokeAiGatewayCommand $command): AiGatewayInvocationResponse
    {
        $response = $this->gateway->invoke(new AiGatewayRequest(
            aiSystemId: $command->aiSystemId,
            requestedByUserId: $command->requestedByUserId,
            promptId: $command->promptId,
            input: $command->input,
        ));

        return AiGatewayInvocationResponse::fromGatewayResponse($response);
    }
}
