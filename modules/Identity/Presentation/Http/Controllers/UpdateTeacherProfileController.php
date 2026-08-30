<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Foundation\Http\Responses\ApiResponse;
use Modules\Identity\Application\Commands\UpdateTeacherProfileCommand;
use Modules\Identity\Application\UseCases\UpdateTeacherProfileHandler;
use Modules\Identity\Presentation\Http\Requests\UpdateTeacherProfileRequest;

final class UpdateTeacherProfileController extends Controller
{
    public function __construct(
        private readonly UpdateTeacherProfileHandler $useCase,
    ) {}

    public function __invoke(UpdateTeacherProfileRequest $request): JsonResponse
    {
        $authenticatedUser = $request->user();

        if ($authenticatedUser === null) {
            abort(401);
        }

        $data = $request->validated();

        $response = $this->useCase->handle(
            new UpdateTeacherProfileCommand(
                userId: (string) $authenticatedUser->getAuthIdentifier(),
                specialties: $data['specialties'] ?? null,
                certifications: $data['certifications'] ?? null,
            ),
        );

        return ApiResponse::success([
            'profile' => $response->toArray(),
        ]);
    }
}
