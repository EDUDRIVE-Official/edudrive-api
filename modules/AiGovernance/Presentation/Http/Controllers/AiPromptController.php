<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\AiGovernance\Application\Commands\ApproveAiPromptCommand;
use Modules\AiGovernance\Application\Commands\CreateAiPromptCommand;
use Modules\AiGovernance\Application\Commands\RetireAiPromptCommand;
use Modules\AiGovernance\Application\Commands\UpdateAiPromptContentCommand;
use Modules\AiGovernance\Application\Queries\GetAiPromptQuery;
use Modules\AiGovernance\Application\Queries\ListAiPromptsQuery;
use Modules\AiGovernance\Application\Responses\AiPromptResponse;
use Modules\AiGovernance\Presentation\Http\Requests\CreateAiPromptRequest;
use Modules\AiGovernance\Presentation\Http\Requests\UpdateAiPromptContentRequest;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Symfony\Component\HttpFoundation\Response;

final class AiPromptController
{
    public function store(CreateAiPromptRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new CreateAiPromptCommand(
            identifier: (string) $data['identifier'],
            purpose: (string) $data['purpose'],
            modelId: isset($data['model_id']) ? (string) $data['model_id'] : null,
            authorId: isset($data['author_id']) ? (string) $data['author_id'] : null,
            content: (string) $data['content'],
        ));
        assert($result instanceof AiPromptResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function index(QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new ListAiPromptsQuery);
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (AiPromptResponse $prompt): array => $prompt->toArray(),
            $result,
        )]);
    }

    public function show(string $promptId, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetAiPromptQuery(promptId: $promptId));
        assert($result instanceof AiPromptResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function updateContent(string $promptId, UpdateAiPromptContentRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new UpdateAiPromptContentCommand(
            promptId: $promptId,
            content: (string) $data['content'],
        ));
        assert($result instanceof AiPromptResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function approve(string $promptId, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new ApproveAiPromptCommand(promptId: $promptId));
        assert($result instanceof AiPromptResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function retire(string $promptId, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new RetireAiPromptCommand(promptId: $promptId));
        assert($result instanceof AiPromptResponse);

        return response()->json(['data' => $result->toArray()]);
    }
}
