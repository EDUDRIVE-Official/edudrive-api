<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Academic\Application\Commands\CreateCourseCommand;
use Modules\Academic\Application\Queries\ListCoursesQuery;
use Modules\Academic\Application\Responses\CourseListItemResponse;
use Modules\Academic\Application\Responses\CreateCourseResponse;
use Modules\Academic\Presentation\Http\Requests\CreateCourseRequest;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Symfony\Component\HttpFoundation\Response;

final class CourseController
{
    public function index(
        QueryBus $queryBus,
    ): JsonResponse {
        $result = $queryBus->ask(
            new ListCoursesQuery,
        );

        assert(is_array($result));

        /** @var list<CourseListItemResponse> $result */
        $data = array_map(
            static fn (CourseListItemResponse $course): array => $course->toArray(),
            $result,
        );

        return response()->json([
            'data' => $data,
        ]);
    }

    public function store(
        CreateCourseRequest $request,
        CommandBus $commandBus,
    ): JsonResponse {
        $validated = $request->validated();

        $result = $commandBus->dispatch(
            new CreateCourseCommand(
                code: (string) $validated['code'],
                title: (string) $validated['title'],
                description: isset($validated['description'])
                    ? (string) $validated['description']
                    : null,
                objectives: isset($validated['objectives'])
                    ? (string) $validated['objectives']
                    : null,
                prerequisites: isset($validated['prerequisites'])
                    ? (string) $validated['prerequisites']
                    : null,
                modality: isset($validated['modality'])
                    ? (string) $validated['modality']
                    : null,
                durationHours: isset($validated['duration_hours'])
                    ? (int) $validated['duration_hours']
                    : null,
            ),
        );

        assert($result instanceof CreateCourseResponse);

        return response()->json(
            [
                'data' => $result->toArray(),
            ],
            Response::HTTP_CREATED,
        );
    }
}
