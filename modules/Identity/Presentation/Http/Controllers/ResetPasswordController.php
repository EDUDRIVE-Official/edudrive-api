<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Foundation\Http\Responses\ApiResponse;
use Modules\Identity\Application\Commands\ResetPasswordCommand;
use Modules\Identity\Application\UseCases\ResetPasswordUseCase;
use Modules\Identity\Presentation\Http\Requests\ResetPasswordRequest;

final class ResetPasswordController extends Controller
{
    public function __construct(
        private readonly ResetPasswordUseCase $useCase,
    ) {}

    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        $this->useCase->execute(
            new ResetPasswordCommand(
                email: (string) $request->string('email'),
                token: (string) $request->string('token'),
                newPassword: (string) $request->string('password'),
            ),
        );

        return ApiResponse::success(
            message: 'Contraseña restablecida correctamente.',
        );
    }
}
