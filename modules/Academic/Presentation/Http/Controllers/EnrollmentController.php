<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Academic\Application\Queries\GetEnrollmentQuery;
use Modules\Academic\Application\Queries\ListEnrollmentsQuery;
use Modules\Academic\Application\Responses\EnrollmentListItemResponse;
use Modules\Academic\Application\Responses\EnrollmentResponse;
use Modules\Academic\Presentation\Http\Requests\ListEnrollmentsRequest;
use Modules\Foundation\Application\Bus\QueryBus;

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
}
