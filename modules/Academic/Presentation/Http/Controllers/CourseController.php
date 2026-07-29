<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Academic\Application\Commands\CreateCourseCommand;
use Modules\Academic\Application\UseCases\CreateCourseHandler;
use Modules\Academic\Presentation\Http\Requests\CreateCourseRequest;
use Symfony\Component\HttpFoundation\Response;

final class CourseController
{
    public function store(
        CreateCourseRequest $request,
        CreateCourseHandler $handler,
    ): JsonResponse {
        $validated = $request->validated();

        $result = $handler->handle(
            new CreateCourseCommand(
                code: (string) $validated['code'],
                title: (string) $validated['title'],
                description: isset($validated['description'])
                    ? (string) $validated['description']
                    : null,
            ),
        );

        return response()->json(
            [
                'data' => $result->toArray(),
            ],
            Response::HTTP_CREATED,
        );
    }
}
