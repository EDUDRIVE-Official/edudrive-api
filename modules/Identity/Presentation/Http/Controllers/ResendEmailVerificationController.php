<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Foundation\Http\Responses\ApiResponse;
use Modules\Identity\Application\Commands\SendEmailVerificationCommand;
use Modules\Identity\Application\UseCases\SendEmailVerificationUseCase;
use Modules\Identity\Presentation\Http\Requests\ResendEmailVerificationRequest;

final class ResendEmailVerificationController extends Controller
{
    public function __construct(
        private readonly SendEmailVerificationUseCase $useCase,
    ) {}

    public function __invoke(ResendEmailVerificationRequest $request): JsonResponse
    {
        $this->useCase->execute(
            new SendEmailVerificationCommand(
                email: (string) $request->string('email'),
            ),
        );

        return ApiResponse::success(
            message: 'Si el correo existe y no ha sido verificado, se enviará un código de verificación.',
        );
    }
}
