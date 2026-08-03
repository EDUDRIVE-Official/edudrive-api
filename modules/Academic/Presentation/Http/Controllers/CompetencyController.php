<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Academic\Application\Commands\AddCompetencyIndicatorCommand;
use Modules\Academic\Application\Commands\AddSubcompetencyCommand;
use Modules\Academic\Application\Commands\CreateCompetencyCommand;
use Modules\Academic\Application\Queries\ListCompetenciesQuery;
use Modules\Academic\Application\Responses\CompetencyResponse;
use Modules\Academic\Presentation\Http\Requests\AddCompetencyIndicatorRequest;
use Modules\Academic\Presentation\Http\Requests\AddSubcompetencyRequest;
use Modules\Academic\Presentation\Http\Requests\CreateCompetencyRequest;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Symfony\Component\HttpFoundation\Response;

final class CompetencyController
{
    public function index(QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new ListCompetenciesQuery);
        assert(is_array($result));

        /** @var list<CompetencyResponse> $result */
        return response()->json(['data' => array_map(
            static fn (CompetencyResponse $competency): array => $competency->toArray(),
            $result,
        )]);
    }

    public function store(CreateCompetencyRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new CreateCompetencyCommand(
            (string) $data['code'],
            (string) $data['title'],
            (string) $data['description'],
            (string) $data['category'],
            (string) $data['mastery_level'],
        ));
        assert($result instanceof CompetencyResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function addSubcompetency(string $competencyId, AddSubcompetencyRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new AddSubcompetencyCommand(
            $competencyId,
            (string) $data['code'],
            (string) $data['title'],
        ));
        assert($result instanceof CompetencyResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function addIndicator(string $competencyId, string $subcompetencyCode, AddCompetencyIndicatorRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new AddCompetencyIndicatorCommand(
            $competencyId,
            $subcompetencyCode,
            (string) $data['code'],
            (string) $data['description'],
        ));
        assert($result instanceof CompetencyResponse);

        return response()->json(['data' => $result->toArray()]);
    }
}
