<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Academic\Application\Commands\ArchiveCourseCommand;
use Modules\Academic\Application\Commands\CreateCourseCommand;
use Modules\Academic\Application\Commands\PublishCourseCommand;
use Modules\Academic\Application\Commands\ReplaceCourseCurriculumCommand;
use Modules\Academic\Application\DTO\CourseModuleInput;
use Modules\Academic\Application\DTO\CourseUnitInput;
use Modules\Academic\Application\Queries\GetCourseCurriculumQuery;
use Modules\Academic\Application\Queries\ListCoursesQuery;
use Modules\Academic\Application\Responses\ArchiveCourseResponse;
use Modules\Academic\Application\Responses\CourseCurriculumResponse;
use Modules\Academic\Application\Responses\CourseListItemResponse;
use Modules\Academic\Application\Responses\CreateCourseResponse;
use Modules\Academic\Application\Responses\PublishCourseResponse;
use Modules\Academic\Presentation\Http\Requests\CreateCourseRequest;
use Modules\Academic\Presentation\Http\Requests\ReplaceCourseCurriculumRequest;
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

    public function publish(
        string $courseId,
        CommandBus $commandBus,
    ): JsonResponse {
        $result = $commandBus->dispatch(
            new PublishCourseCommand(courseId: $courseId),
        );

        assert($result instanceof PublishCourseResponse);

        return response()->json([
            'data' => $result->toArray(),
        ]);
    }

    public function curriculum(
        string $courseId,
        QueryBus $queryBus,
    ): JsonResponse {
        $result = $queryBus->ask(
            new GetCourseCurriculumQuery(courseId: $courseId),
        );

        assert($result instanceof CourseCurriculumResponse);

        return response()->json([
            'data' => $result->toArray(),
        ]);
    }

    public function replaceCurriculum(
        string $courseId,
        ReplaceCourseCurriculumRequest $request,
        CommandBus $commandBus,
    ): JsonResponse {
        $validated = $request->validated();
        $moduleValues = $validated['modules'];
        assert(is_array($moduleValues));

        $modules = [];

        foreach ($moduleValues as $module) {
            assert(is_array($module));
            /** @var array<string, mixed> $module */
            $modules[] = $this->moduleInput($module);
        }

        $result = $commandBus->dispatch(
            new ReplaceCourseCurriculumCommand(
                courseId: $courseId,
                modules: $modules,
            ),
        );

        assert($result instanceof CourseCurriculumResponse);

        return response()->json([
            'data' => $result->toArray(),
        ]);
    }

    public function archive(
        string $courseId,
        CommandBus $commandBus,
    ): JsonResponse {
        $result = $commandBus->dispatch(
            new ArchiveCourseCommand(courseId: $courseId),
        );

        assert($result instanceof ArchiveCourseResponse);

        return response()->json([
            'data' => $result->toArray(),
        ]);
    }

    /** @param array<string, mixed> $module */
    private function moduleInput(array $module): CourseModuleInput
    {
        $unitValues = $module['units'];
        assert(is_array($unitValues));

        $units = [];

        foreach ($unitValues as $unit) {
            assert(is_array($unit));
            /** @var array<string, mixed> $unit */
            $units[] = $this->unitInput($unit);
        }

        return new CourseModuleInput(
            id: (string) $module['id'],
            code: (string) $module['code'],
            title: (string) $module['title'],
            description: (string) $module['description'],
            objectives: isset($module['objectives']) ? (string) $module['objectives'] : null,
            durationMinutes: isset($module['duration_minutes']) ? (int) $module['duration_minutes'] : null,
            position: (int) $module['position'],
            prerequisiteModuleIds: $this->stringList($module['prerequisite_module_ids']),
            units: $units,
        );
    }

    /** @param array<string, mixed> $unit */
    private function unitInput(array $unit): CourseUnitInput
    {
        return new CourseUnitInput(
            id: (string) $unit['id'],
            code: (string) $unit['code'],
            title: (string) $unit['title'],
            description: (string) $unit['description'],
            objectives: isset($unit['objectives']) ? (string) $unit['objectives'] : null,
            durationMinutes: isset($unit['duration_minutes']) ? (int) $unit['duration_minutes'] : null,
            position: (int) $unit['position'],
            prerequisiteUnitIds: $this->stringList($unit['prerequisite_unit_ids']),
        );
    }

    /** @return list<string> */
    private function stringList(mixed $value): array
    {
        assert(is_array($value));

        return array_values(array_map(
            static fn (mixed $item): string => (string) $item,
            $value,
        ));
    }
}
