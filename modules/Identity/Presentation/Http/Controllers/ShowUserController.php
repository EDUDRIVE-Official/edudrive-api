<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Identity\Application\UseCases\GetUserUseCase;

final class ShowUserController extends Controller
{
    public function __construct(
        private readonly GetUserUseCase $useCase,
    ) {}

    public function __invoke(string $userId): JsonResponse
    {
        $user = $this->useCase->execute($userId);

        return response()->json(['data' => $user->toArray()]);
    }
}
