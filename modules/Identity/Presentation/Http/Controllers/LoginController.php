<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Identity\Application\Commands\LoginUserCommand;
use Modules\Identity\Application\UseCases\LoginUserUseCase;
use Modules\Identity\Presentation\Http\Requests\LoginRequest;

final class LoginController extends Controller
{
    public function __construct(
        private readonly LoginUserUseCase $useCase,
    ) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        $response = $this->useCase->execute(
            new LoginUserCommand(
                email: (string) $request->string('email'),
                password: (string) $request->string('password'),
                tokenName: (string) ($request->input('token_name') ?? 'api'),
            ),
        );

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $response->userId,
                    'name' => $response->name,
                    'email' => $response->email,
                    'status' => $response->status,
                ],
                'token' => [
                    'type' => $response->tokenType,
                    'access_token' => $response->accessToken,
                ],
            ],
        ]);
    }
}
