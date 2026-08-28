<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Identity\Application\UseCases\ExportMyDataUseCase;

final class ExportMyDataController extends Controller
{
    public function __construct(
        private readonly ExportMyDataUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $authenticatedUser = $request->user();

        if ($authenticatedUser === null) {
            abort(401);
        }

        $response = $this->useCase->execute(
            (string) $authenticatedUser->getAuthIdentifier(),
        );

        return response()->json(['data' => $response->toArray()]);
    }
}
