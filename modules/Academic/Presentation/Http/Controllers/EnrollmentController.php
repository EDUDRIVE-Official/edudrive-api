<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Academic\Application\Commands\ActivateEnrollmentCommand;
use Modules\Academic\Application\Commands\CancelEnrollmentCommand;
use Modules\Academic\Application\Commands\CompleteEnrollmentCommand;
use Modules\Academic\Application\Commands\CreateBulkEnrollmentsCommand;
use Modules\Academic\Application\Commands\CreateEnrollmentCommand;
use Modules\Academic\Application\Commands\CreateInstitutionalEnrollmentCommand;
use Modules\Academic\Application\Queries\GetEnrollmentQuery;
use Modules\Academic\Application\Queries\ListEnrollmentsQuery;
use Modules\Academic\Application\Responses\BulkEnrollmentResponse;
use Modules\Academic\Application\Responses\EnrollmentListItemResponse;
use Modules\Academic\Application\Responses\EnrollmentResponse;
use Modules\Academic\Presentation\Http\Requests\CreateBulkEnrollmentsRequest;
use Modules\Academic\Presentation\Http\Requests\CreateEnrollmentRequest;
use Modules\Academic\Presentation\Http\Requests\CreateInstitutionalEnrollmentRequest;
use Modules\Academic\Presentation\Http\Requests\ListEnrollmentsRequest;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Symfony\Component\HttpFoundation\Response;

final class EnrollmentController
{
    public function index(ListEnrollmentsRequest $request, QueryBus $queryBus): JsonResponse
    {
        $data = $request->validated();
        $result = $queryBus->ask(new ListEnrollmentsQuery(
            courseId: isset($data['course_id']) ? (string) $data['course_id'] : null,
            userId: isset($data['user_id']) ? (string) $data['user_id'] : null,
            organizationId: isset($data['organization_id']) ? (string) $data['organization_id'] : null,
            status: isset($data['status']) ? (string) $data['status'] : null,
            source: isset($data['source']) ? (string) $data['source'] : null,
        ));
        assert(is_array($result));

        /** @var list<EnrollmentListItemResponse> $result */
        return response()->json(['data' => array_map(
            static fn (EnrollmentListItemResponse $enrollment): array => $enrollment->toArray(),
            $result,
        )]);
    }

    public function show(string $enrollmentId, QueryBus $queryBus): JsonResponse
    {
        $result = $queryBus->ask(new GetEnrollmentQuery(enrollmentId: $enrollmentId));
        assert($result instanceof EnrollmentResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function store(CreateEnrollmentRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new CreateEnrollmentCommand(
            courseId: (string) $data['course_id'],
            userId: (string) $data['user_id'],
            status: (string) $data['status'],
            source: 'individual',
            startsAt: isset($data['starts_at']) ? (string) $data['starts_at'] : null,
            endsAt: isset($data['ends_at']) ? (string) $data['ends_at'] : null,
        ));
        assert($result instanceof EnrollmentResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function bulk(CreateBulkEnrollmentsRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new CreateBulkEnrollmentsCommand(
            courseId: (string) $data['course_id'],
            userIds: (array) $data['user_ids'],
            status: (string) $data['status'],
            source: 'bulk',
            startsAt: isset($data['starts_at']) ? (string) $data['starts_at'] : null,
            endsAt: isset($data['ends_at']) ? (string) $data['ends_at'] : null,
        ));
        assert($result instanceof BulkEnrollmentResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function institutional(CreateInstitutionalEnrollmentRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new CreateInstitutionalEnrollmentCommand(
            courseId: (string) $data['course_id'],
            userId: (string) $data['user_id'],
            organizationId: (string) $data['organization_id'],
            status: (string) $data['status'],
            startsAt: isset($data['starts_at']) ? (string) $data['starts_at'] : null,
            endsAt: isset($data['ends_at']) ? (string) $data['ends_at'] : null,
        ));
        assert($result instanceof EnrollmentResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }

    public function activate(string $enrollmentId, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new ActivateEnrollmentCommand(enrollmentId: $enrollmentId));
        assert($result instanceof EnrollmentResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function complete(string $enrollmentId, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new CompleteEnrollmentCommand(enrollmentId: $enrollmentId));
        assert($result instanceof EnrollmentResponse);

        return response()->json(['data' => $result->toArray()]);
    }

    public function cancel(string $enrollmentId, CommandBus $commandBus): JsonResponse
    {
        $result = $commandBus->dispatch(new CancelEnrollmentCommand(enrollmentId: $enrollmentId));
        assert($result instanceof EnrollmentResponse);

        return response()->json(['data' => $result->toArray()]);
    }
}
