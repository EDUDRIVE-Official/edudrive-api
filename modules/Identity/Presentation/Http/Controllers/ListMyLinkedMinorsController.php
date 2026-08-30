<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Http\Responses\ApiResponse;
use Modules\Identity\Application\Queries\ListMyLinkedMinorsQuery;
use Modules\Identity\Application\Responses\LinkedMinorResponse;
use Modules\Identity\Application\UseCases\ListMyLinkedMinorsHandler;

final class ListMyLinkedMinorsController extends Controller
{
    public function __construct(
        private readonly ListMyLinkedMinorsHandler $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $authenticatedUser = $request->user();

        if ($authenticatedUser === null) {
            abort(401);
        }

        $response = $this->useCase->handle(
            new ListMyLinkedMinorsQuery(
                guardianUserId: (string) $authenticatedUser->getAuthIdentifier(),
            ),
        );

        return ApiResponse::success([
            'minors' => array_map(
                static fn (LinkedMinorResponse $minor): array => $minor->toArray(),
                $response,
            ),
        ]);
    }
}
