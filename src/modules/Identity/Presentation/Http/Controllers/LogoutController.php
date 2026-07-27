<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Http\Responses\ApiResponse;
use Modules\Identity\Application\UseCases\LogoutUserUseCase;

final class LogoutController extends Controller
{
    public function __construct(
        private readonly LogoutUserUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $authenticatedUser = $request->user();

        if ($authenticatedUser === null) {
            abort(401);
        }

        $accessToken = $authenticatedUser->currentAccessToken();

        $this->useCase->execute((string) $accessToken->getKey());

        return ApiResponse::success(
            message: 'Sesión cerrada correctamente.',
        );
    }
}
