<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Foundation\Http\Responses\ApiResponse;
use Modules\Identity\Application\Commands\RequestPasswordResetCommand;
use Modules\Identity\Application\UseCases\RequestPasswordResetUseCase;
use Modules\Identity\Presentation\Http\Requests\ForgotPasswordRequest;

final class ForgotPasswordController extends Controller
{
    public function __construct(
        private readonly RequestPasswordResetUseCase $useCase,
    ) {}

    public function __invoke(ForgotPasswordRequest $request): JsonResponse
    {
        $this->useCase->execute(
            new RequestPasswordResetCommand(
                email: (string) $request->string('email'),
            ),
        );

        return ApiResponse::success(
            message: 'Si el correo existe en nuestro sistema, se enviará un código de recuperación.',
        );
    }
}
