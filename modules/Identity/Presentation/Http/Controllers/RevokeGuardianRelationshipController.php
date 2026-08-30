<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Http\Responses\ApiResponse;
use Modules\Identity\Application\Commands\RevokeGuardianRelationshipCommand;
use Modules\Identity\Application\UseCases\RevokeGuardianRelationshipHandler;

final class RevokeGuardianRelationshipController extends Controller
{
    public function __construct(
        private readonly RevokeGuardianRelationshipHandler $useCase,
    ) {}

    public function __invoke(Request $request, string $relationshipId): JsonResponse
    {
        $this->useCase->handle(
            new RevokeGuardianRelationshipCommand(
                relationshipId: $relationshipId,
                actorId: (string) $request->user()?->getAuthIdentifier(),
            ),
        );

        return ApiResponse::success(
            message: 'Relación tutor-menor revocada correctamente.',
        );
    }
}
