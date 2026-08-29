<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Foundation\Http\Responses\ApiResponse;
use Modules\Identity\Application\DTO\RegisterUserCommand;
use Modules\Identity\Application\UseCases\RegisterUserUseCase;
use Modules\Identity\Presentation\Http\Requests\RegisterUserRequest;

final class AuthController extends Controller
{
    public function __construct(
        private readonly RegisterUserUseCase $registerUser,
    ) {}

    public function register(RegisterUserRequest $request): JsonResponse
    {
        $user = $this->registerUser->execute(
            new RegisterUserCommand(
                name: (string) $request->input('name'),
                email: (string) $request->input('email'),
                password: (string) $request->input('password'),
                dateOfBirth: $request->input('date_of_birth') === null
                    ? null
                    : (string) $request->input('date_of_birth'),
            ),
        );

        return ApiResponse::created(
            [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
            ],
            'Usuario registrado correctamente.',
        );
    }
}
