<?php

declare(strict_types=1);

namespace Modules\Integration\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Academic\Application\Commands\CreateBulkInstitutionalEnrollmentsCommand;
use Modules\Academic\Application\Responses\BulkEnrollmentResponse;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Integration\Presentation\Http\Requests\ExternalCreateInstitutionalEnrollmentsRequest;
use Symfony\Component\HttpFoundation\Response;

final class ExternalInstitutionalEnrollmentController
{
    public function store(ExternalCreateInstitutionalEnrollmentsRequest $request, CommandBus $commandBus): JsonResponse
    {
        $data = $request->validated();
        $result = $commandBus->dispatch(new CreateBulkInstitutionalEnrollmentsCommand(
            courseId: (string) $data['course_id'],
            organizationId: (string) $data['organization_id'],
            userIds: $data['user_ids'],
            status: isset($data['status']) ? (string) $data['status'] : 'pending',
            startsAt: isset($data['starts_at']) ? (string) $data['starts_at'] : null,
            endsAt: isset($data['ends_at']) ? (string) $data['ends_at'] : null,
        ));
        assert($result instanceof BulkEnrollmentResponse);

        return response()->json(['data' => $result->toArray()], Response::HTTP_CREATED);
    }
}
