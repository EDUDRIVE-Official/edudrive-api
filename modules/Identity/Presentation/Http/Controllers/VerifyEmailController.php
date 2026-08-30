<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Foundation\Http\Responses\ApiResponse;
use Modules\Identity\Application\Commands\VerifyEmailCommand;
use Modules\Identity\Application\UseCases\VerifyEmailUseCase;
use Modules\Identity\Presentation\Http\Requests\VerifyEmailRequest;

final class VerifyEmailController extends Controller
{
    public function __construct(
        private readonly VerifyEmailUseCase $useCase,
    ) {}

    public function __invoke(VerifyEmailRequest $request): JsonResponse
    {
        $this->useCase->execute(
            new VerifyEmailCommand(
                email: (string) $request->string('email'),
                token: (string) $request->string('token'),
            ),
        );

        return ApiResponse::success(
            message: 'Correo verificado correctamente.',
        );
    }
}
