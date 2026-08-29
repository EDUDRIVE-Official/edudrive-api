<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\AiGovernance\Application\Commands\InvokeAiGatewayCommand;
use Modules\AiGovernance\Application\Responses\AiGatewayInvocationResponse;
use Modules\AiGovernance\Presentation\Http\Requests\InvokeAiGatewayRequest;
use Modules\Foundation\Application\Bus\CommandBus;

final class AiGatewayController
{
    public function invoke(InvokeAiGatewayRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new InvokeAiGatewayCommand(
            aiSystemId: (string) $data['ai_system_id'],
            requestedByUserId: (string) $request->user()?->getAuthIdentifier(),
            promptId: isset($data['prompt_id']) ? (string) $data['prompt_id'] : null,
            input: (string) $data['input'],
        ));
        assert($result instanceof AiGatewayInvocationResponse);

        return response()->json(['data' => $result->toArray()]);
    }
}
