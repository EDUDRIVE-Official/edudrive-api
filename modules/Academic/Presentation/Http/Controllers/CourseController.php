<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use League\Csv\Reader;
use Modules\Academic\Application\Commands\ApproveCourseCommand;
use Modules\Academic\Application\Commands\ArchiveCourseCommand;
use Modules\Academic\Application\Commands\BulkImportCoursesCommand;
use Modules\Academic\Application\Commands\CreateCourseCommand;
use Modules\Academic\Application\Commands\ExportCoursesCommand;
use Modules\Academic\Application\Commands\PublishCourseCommand;
use Modules\Academic\Application\Commands\ReopenCourseCommand;
use Modules\Academic\Application\Commands\ReplaceCourseCurriculumCommand;
use Modules\Academic\Application\Commands\ReplaceUnitContentCommand;
use Modules\Academic\Application\Commands\SendCourseBackToDraftCommand;
use Modules\Academic\Application\Commands\SubmitCourseForReviewCommand;
use Modules\Academic\Application\DTO\ContentBlockInput;
use Modules\Academic\Application\DTO\CourseModuleInput;
use Modules\Academic\Application\DTO\CourseUnitInput;
use Modules\Academic\Application\DTO\LessonInput;
use Modules\Academic\Application\Queries\GetCourseCurriculumQuery;
use Modules\Academic\Application\Queries\GetCourseVersionQuery;
use Modules\Academic\Application\Queries\GetUnitContentQuery;
use Modules\Academic\Application\Queries\ListCoursesQuery;
use Modules\Academic\Application\Queries\ListCourseVersionsQuery;
use Modules\Academic\Application\Responses\ArchiveCourseResponse;
use Modules\Academic\Application\Responses\CourseCurriculumResponse;
use Modules\Academic\Application\Responses\CourseListItemResponse;
use Modules\Academic\Application\Responses\CourseStatusResponse;
use Modules\Academic\Application\Responses\CourseVersionListItemResponse;
use Modules\Academic\Application\Responses\CourseVersionResponse;
use Modules\Academic\Application\Responses\CreateCourseResponse;
use Modules\Academic\Application\Responses\PublishCourseResponse;
use Modules\Academic\Application\Responses\UnitContentResponse;
use Modules\Academic\Presentation\Http\Requests\BulkImportCoursesRequest;
use Modules\Academic\Presentation\Http\Requests\CreateCourseRequest;
use Modules\Academic\Presentation\Http\Requests\ReplaceCourseCurriculumRequest;
use Modules\Academic\Presentation\Http\Requests\ReplaceUnitContentRequest;
use Modules\AsyncProcessing\Application\Responses\AsyncJobResponse;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Symfony\Component\HttpFoundation\Response;

final class CourseController
{
    private const int MAX_IMPORT_ROWS = 500;

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

    public function bulkImport(BulkImportCoursesRequest $request, CommandBus $commandBus): JsonResponse
    {
        $file = $request->file('file');
        assert($file !== null);

        $csv = Reader::createFromPath((string) $file->getRealPath());
        $csv->setHeaderOffset(0);

        $rows = [];
        foreach ($csv->getRecords() as $record) {
            $rows[] = [
                'code' => (string) ($record['code'] ?? ''),
                'title' => (string) ($record['title'] ?? ''),
                'description' => (string) ($record['description'] ?? ''),
                'objectives' => (string) ($record['objectives'] ?? ''),
                'prerequisites' => (string) ($record['prerequisites'] ?? ''),
                'modality' => (string) ($record['modality'] ?? ''),
                'duration_hours' => (string) ($record['duration_hours'] ?? ''),
            ];
        }

        if (count($rows) > self::MAX_IMPORT_ROWS) {
            throw ValidationException::withMessages([
                'file' => [sprintf('El archivo no puede contener más de %d filas.', self::MAX_IMPORT_ROWS)],
            ]);
        }

        $result = $commandBus->dispatch(new BulkImportCoursesCommand(
            rows: $rows,
            requestedByUserId: (string) $request->user()?->getAuthIdentifier(),
        ));
        assert($result instanceof AsyncJobResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_ACCEPTED);
    }

    public function export(Request $request, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new ExportCoursesCommand(
            requestedByUserId: (string) $request->user()?->getAuthIdentifier(),
        ));
        assert($result instanceof AsyncJobResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_ACCEPTED);
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

    public function submitForReview(
        string $courseId,
        CommandBus $commandBus,
    ): JsonResponse {
        $result = $commandBus->dispatch(
            new SubmitCourseForReviewCommand(courseId: $courseId),
        );

        assert($result instanceof CourseStatusResponse);

        return response()->json([
            'data' => $result->toArray(),
        ]);
    }

    public function approve(
        string $courseId,
        CommandBus $commandBus,
    ): JsonResponse {
        $result = $commandBus->dispatch(
            new ApproveCourseCommand(courseId: $courseId),
        );

        assert($result instanceof CourseStatusResponse);

        return response()->json([
            'data' => $result->toArray(),
        ]);
    }

    public function sendBackToDraft(
        string $courseId,
        CommandBus $commandBus,
    ): JsonResponse {
        $result = $commandBus->dispatch(
            new SendCourseBackToDraftCommand(courseId: $courseId),
        );

        assert($result instanceof CourseStatusResponse);

        return response()->json([
            'data' => $result->toArray(),
        ]);
    }

    public function reopen(
        string $courseId,
        CommandBus $commandBus,
    ): JsonResponse {
        $result = $commandBus->dispatch(
            new ReopenCourseCommand(courseId: $courseId),
        );

        assert($result instanceof CourseStatusResponse);

        return response()->json([
            'data' => $result->toArray(),
        ]);
    }

    public function versions(
        string $courseId,
        QueryBus $queryBus,
    ): JsonResponse {
        $result = $queryBus->ask(
            new ListCourseVersionsQuery(courseId: $courseId),
        );

        assert(is_array($result));

        /** @var list<CourseVersionListItemResponse> $result */
        $data = array_map(
            static fn (CourseVersionListItemResponse $version): array => $version->toArray(),
            $result,
        );

        return response()->json([
            'data' => $data,
        ]);
    }

    public function version(
        string $courseId,
        int $versionNumber,
        QueryBus $queryBus,
    ): JsonResponse {
        $result = $queryBus->ask(
            new GetCourseVersionQuery(courseId: $courseId, versionNumber: $versionNumber),
        );

        assert($result instanceof CourseVersionResponse);

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

    public function unitContent(
        string $courseId,
        string $unitId,
        QueryBus $queryBus,
    ): JsonResponse {
        $result = $queryBus->ask(new GetUnitContentQuery($courseId, $unitId));
        assert($result instanceof UnitContentResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function replaceUnitContent(
        string $courseId,
        string $unitId,
        ReplaceUnitContentRequest $request,
        CommandBus $commandBus,
    ): JsonResponse {
        $validated = $request->validated();
        $lessonValues = $validated['lessons'];
        assert(is_array($lessonValues));
        $lessons = [];

        foreach ($lessonValues as $lesson) {
            assert(is_array($lesson));
            $blockValues = $lesson['blocks'];
            assert(is_array($blockValues));
            $blocks = [];

            foreach ($blockValues as $block) {
                assert(is_array($block));
                $payload = $block['payload'];
                assert(is_array($payload));
                /** @var array<string, mixed> $payload */
                $blocks[] = new ContentBlockInput(
                    (string) $block['id'],
                    (string) $block['type'],
                    (int) $block['position'],
                    $payload,
                );
            }

            $lessons[] = new LessonInput(
                (string) $lesson['id'],
                (string) $lesson['code'],
                (string) $lesson['title'],
                isset($lesson['summary']) ? (string) $lesson['summary'] : null,
                isset($lesson['duration_minutes']) ? (int) $lesson['duration_minutes'] : null,
                (int) $lesson['position'],
                $blocks,
            );
        }

        $result = $commandBus->dispatch(new ReplaceUnitContentCommand($courseId, $unitId, $lessons));
        assert($result instanceof UnitContentResponse);

        return response()->json(['data' => $result->toArray()]);
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
