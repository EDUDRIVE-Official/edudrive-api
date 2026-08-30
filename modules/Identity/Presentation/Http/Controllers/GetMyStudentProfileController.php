<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Http\Responses\ApiResponse;
use Modules\Identity\Application\Queries\GetMyStudentProfileQuery;
use Modules\Identity\Application\UseCases\GetMyStudentProfileHandler;

final class GetMyStudentProfileController extends Controller
{
    public function __construct(
        private readonly GetMyStudentProfileHandler $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $authenticatedUser = $request->user();

        if ($authenticatedUser === null) {
            abort(401);
        }

        $response = $this->useCase->handle(
            new GetMyStudentProfileQuery(
                userId: (string) $authenticatedUser->getAuthIdentifier(),
            ),
        );

        return ApiResponse::success([
            'profile' => $response->toArray(),
        ]);
    }
}
