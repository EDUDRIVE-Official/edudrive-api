<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Foundation\Http\Responses\ApiResponse;
use Modules\Identity\Application\Commands\CreateGuardianRelationshipCommand;
use Modules\Identity\Application\UseCases\CreateGuardianRelationshipHandler;
use Modules\Identity\Presentation\Http\Requests\CreateGuardianRelationshipRequest;

final class CreateGuardianRelationshipController extends Controller
{
    public function __construct(
        private readonly CreateGuardianRelationshipHandler $useCase,
    ) {}

    public function __invoke(CreateGuardianRelationshipRequest $request): JsonResponse
    {
        $data = $request->validated();

        $response = $this->useCase->handle(
            new CreateGuardianRelationshipCommand(
                guardianUserId: (string) $data['guardian_user_id'],
                minorUserId: (string) $data['minor_user_id'],
                actorId: (string) $request->user()?->getAuthIdentifier(),
            ),
        );

        return ApiResponse::created([
            'relationship' => $response->toArray(),
        ]);
    }
}
