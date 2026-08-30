<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Http\Responses\ApiResponse;
use Modules\Identity\Application\Queries\GetLinkedMinorProgressQuery;
use Modules\Identity\Application\UseCases\GetLinkedMinorProgressHandler;

final class GetLinkedMinorProgressController extends Controller
{
    public function __construct(
        private readonly GetLinkedMinorProgressHandler $useCase,
    ) {}

    public function __invoke(Request $request, string $minorUserId): JsonResponse
    {
        $authenticatedUser = $request->user();

        if ($authenticatedUser === null) {
            abort(401);
        }

        $response = $this->useCase->handle(
            new GetLinkedMinorProgressQuery(
                guardianUserId: (string) $authenticatedUser->getAuthIdentifier(),
                minorUserId: $minorUserId,
            ),
        );

        return ApiResponse::success([
            'profile' => $response->toArray(),
        ]);
    }
}
