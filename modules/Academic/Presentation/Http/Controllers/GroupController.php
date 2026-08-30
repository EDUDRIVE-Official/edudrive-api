<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Application\Commands\AssignGroupTeacherCommand;
use Modules\Academic\Application\Commands\CreateGroupCommand;
use Modules\Academic\Application\Queries\ListGroupsQuery;
use Modules\Academic\Application\Responses\GroupResponse;
use Modules\Academic\Presentation\Http\Requests\AssignGroupTeacherRequest;
use Modules\Academic\Presentation\Http\Requests\CreateGroupRequest;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Symfony\Component\HttpFoundation\Response;

final class GroupController
{
    public function index(Request $request, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new ListGroupsQuery(
            courseId: $request->query('course_id') === null ? null : (string) $request->query('course_id'),
        ));
        assert(is_array($result));

        /** @var list<GroupResponse> $result */
        return response()->json(['data' => array_map(
            static fn (GroupResponse $group): array => $group->toArray(),
            $result,
        )]);
    }

    public function store(CreateGroupRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new CreateGroupCommand(
            courseId: (string) $data['course_id'],
            organizationId: isset($data['organization_id']) ? (string) $data['organization_id'] : null,
            name: (string) $data['name'],
            teacherId: isset($data['teacher_id']) ? (string) $data['teacher_id'] : null,
            startsAt: (string) $data['starts_at'],
            endsAt: (string) $data['ends_at'],
        ));
        assert($result instanceof GroupResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function assignTeacher(string $groupId, AssignGroupTeacherRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new AssignGroupTeacherCommand(
            groupId: $groupId,
            teacherId: isset($data['teacher_id']) ? (string) $data['teacher_id'] : null,
        ));
        assert($result instanceof GroupResponse);

        return response()->json(['data' => $result->toArray()]);
    }
}
