<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Identity\Application\Responses\UserResponse;
use Modules\Identity\Application\UseCases\ListUsersUseCase;

final class ListUsersController extends Controller
{
    public function __construct(
        private readonly ListUsersUseCase $useCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        $users = $this->useCase->execute();

        return response()->json([
            'data' => array_map(
                static fn (UserResponse $user): array => $user->toArray(),
                $users,
            ),
        ]);
    }
}
