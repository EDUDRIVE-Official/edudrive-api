<?php

declare(strict_types=1);

namespace Modules\Notification\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Notification\Application\Commands\CreateCommunicationTemplateCommand;
use Modules\Notification\Application\Commands\RetireCommunicationTemplateCommand;
use Modules\Notification\Application\Commands\UpdateCommunicationTemplateCommand;
use Modules\Notification\Application\Queries\GetCommunicationTemplateQuery;
use Modules\Notification\Application\Queries\ListCommunicationTemplatesQuery;
use Modules\Notification\Application\Queries\PreviewCommunicationTemplateQuery;
use Modules\Notification\Application\Responses\CommunicationTemplateResponse;
use Modules\Notification\Application\Responses\RenderedTemplateResponse;
use Modules\Notification\Presentation\Http\Requests\CreateCommunicationTemplateRequest;
use Modules\Notification\Presentation\Http\Requests\PreviewCommunicationTemplateRequest;
use Modules\Notification\Presentation\Http\Requests\RetireCommunicationTemplateRequest;
use Modules\Notification\Presentation\Http\Requests\UpdateCommunicationTemplateRequest;
use Symfony\Component\HttpFoundation\Response;

final class CommunicationTemplateController
{
    public function store(CreateCommunicationTemplateRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new CreateCommunicationTemplateCommand(
            code: (string) $data['code'],
            locale: (string) $data['locale'],
            subjectTemplate: (string) $data['subject_template'],
            bodyTemplate: (string) $data['body_template'],
            variables: $data['variables'],
        ));
        assert($result instanceof CommunicationTemplateResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function index(QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new ListCommunicationTemplatesQuery);
        assert(is_array($result));

        return response()->json(['data' => array_map(
            static fn (CommunicationTemplateResponse $template): array => $template->toArray(),
            $result,
        )]);
    }

    public function show(string $templateId, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetCommunicationTemplateQuery(templateId: $templateId));
        assert($result instanceof CommunicationTemplateResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function update(string $templateId, UpdateCommunicationTemplateRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new UpdateCommunicationTemplateCommand(
            templateId: $templateId,
            subjectTemplate: (string) $data['subject_template'],
            bodyTemplate: (string) $data['body_template'],
            variables: $data['variables'],
        ));
        assert($result instanceof CommunicationTemplateResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function retire(string $templateId, RetireCommunicationTemplateRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new RetireCommunicationTemplateCommand(
            templateId: $templateId,
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
        ));
        assert($result instanceof CommunicationTemplateResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function preview(string $templateId, PreviewCommunicationTemplateRequest $request, QueryBus $queryBus): JsonResponse
    {
        $data = $request->validated();
        $result = $queryBus->ask(new PreviewCommunicationTemplateQuery(
            templateId: $templateId,
            variables: $data['variables'],
        ));
        assert($result instanceof RenderedTemplateResponse);

        return response()->json(['data' => $result->toArray()]);
    }
}
