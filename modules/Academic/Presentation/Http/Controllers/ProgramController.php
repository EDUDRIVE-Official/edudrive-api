<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Academic\Application\Commands\ArchiveProgramCommand;
use Modules\Academic\Application\Commands\ChangeProgramAudienceCommand;
use Modules\Academic\Application\Commands\CreateProgramCommand;
use Modules\Academic\Application\Commands\PublishProgramCommand;
use Modules\Academic\Application\Commands\ReplaceProgramCoursesCommand;
use Modules\Academic\Application\Queries\ListProgramsQuery;
use Modules\Academic\Application\Responses\ProgramResponse;
use Modules\Academic\Presentation\Http\Requests\ChangeProgramAudienceRequest;
use Modules\Academic\Presentation\Http\Requests\CreateProgramRequest;
use Modules\Academic\Presentation\Http\Requests\ReplaceProgramCoursesRequest;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Symfony\Component\HttpFoundation\Response;

final class ProgramController
{
    public function index(QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new ListProgramsQuery);
        assert(is_array($result));

        /** @var list<ProgramResponse> $result */
        return response()->json(['data' => array_map(
            static fn (ProgramResponse $program): array => $program->toArray(),
            $result,
        )]);
    }

    public function store(CreateProgramRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new CreateProgramCommand(
            code: (string) $data['code'],
            title: (string) $data['title'],
            description: (string) $data['description'],
            minAge: isset($data['min_age']) ? (int) $data['min_age'] : null,
            maxAge: isset($data['max_age']) ? (int) $data['max_age'] : null,
            licenseStages: self::stringList($data['license_stages'] ?? []),
            contexts: self::stringList($data['contexts'] ?? []),
            vehicleTypes: self::stringList($data['vehicle_types'] ?? []),
        ));
        assert($result instanceof ProgramResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function changeAudience(
        string $programId,
        ChangeProgramAudienceRequest $request,
        CommandBus $commandBus,
    ): JsonResponse {
        $data = $request->validated();
        $result = $commandBus->dispatch(new ChangeProgramAudienceCommand(
            programId: $programId,
            minAge: isset($data['min_age']) ? (int) $data['min_age'] : null,
            maxAge: isset($data['max_age']) ? (int) $data['max_age'] : null,
            licenseStages: self::stringList($data['license_stages'] ?? []),
            contexts: self::stringList($data['contexts'] ?? []),
            vehicleTypes: self::stringList($data['vehicle_types'] ?? []),
        ));
        assert($result instanceof ProgramResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function replaceCourses(
        string $programId,
        ReplaceProgramCoursesRequest $request,
        CommandBus $commandBus,
    ): JsonResponse {
        $data = $request->validated();
        $result = $commandBus->dispatch(new ReplaceProgramCoursesCommand(
            programId: $programId,
            courseIds: self::stringList($data['course_ids']),
        ));
        assert($result instanceof ProgramResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function publish(string $programId, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new PublishProgramCommand(programId: $programId));
        assert($result instanceof ProgramResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function archive(string $programId, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new ArchiveProgramCommand(programId: $programId));
        assert($result instanceof ProgramResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $values): array
    {
        assert(is_array($values));

        return array_values(array_map(static fn (mixed $value): string => (string) $value, $values));
    }
}
