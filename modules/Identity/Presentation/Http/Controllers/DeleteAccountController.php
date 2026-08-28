<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Foundation\Http\Responses\ApiResponse;
use Modules\Identity\Application\Commands\DeleteAccountCommand;
use Modules\Identity\Application\UseCases\DeleteAccountUseCase;

final class DeleteAccountController extends Controller
{
    public function __construct(
        private readonly DeleteAccountUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $authenticatedUser = $request->user();

        if ($authenticatedUser === null) {
            abort(401);
        }

        $userId = (string) $authenticatedUser->getAuthIdentifier();

        $this->useCase->execute(
            new DeleteAccountCommand(
                userId: $userId,
                actorId: $userId,
            ),
        );

        return ApiResponse::success(
            message: 'Cuenta eliminada correctamente.',
        );
    }
}
