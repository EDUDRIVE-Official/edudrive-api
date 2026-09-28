<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Foundation\Http\Responses\ApiResponse;
use Modules\Identity\Application\Commands\UpdateStudentProfileCommand;
use Modules\Identity\Application\Commands\UpdateUserDateOfBirthCommand;
use Modules\Identity\Application\UseCases\UpdateStudentProfileHandler;
use Modules\Identity\Application\UseCases\UpdateUserDateOfBirthHandler;
use Modules\Identity\Presentation\Http\Requests\UpdateStudentProfileRequest;

final class UpdateStudentProfileController extends Controller
{
    public function __construct(
        private readonly UpdateStudentProfileHandler $useCase,
        private readonly UpdateUserDateOfBirthHandler $updateDateOfBirth,
    ) {}

    public function __invoke(UpdateStudentProfileRequest $request): JsonResponse
    {
        $authenticatedUser = $request->user();

        if ($authenticatedUser === null) {
            abort(401);
        }

        $data = $request->validated();

        $response = $this->useCase->handle(
            new UpdateStudentProfileCommand(
                userId: (string) $authenticatedUser->getAuthIdentifier(),
                educationLevel: $data['education_level'] ?? null,
                accessibilityNeeds: $data['accessibility_needs'] ?? null,
                learningPreferences: $data['learning_preferences'] ?? null,
            ),
        );
        if (array_key_exists('date_of_birth', $data)) {
            $this->updateDateOfBirth->handle(new UpdateUserDateOfBirthCommand(
                userId: (string) $authenticatedUser->getAuthIdentifier(),
                dateOfBirth: isset($data['date_of_birth']) ? (string) $data['date_of_birth'] : null,
            ));
        }

        return ApiResponse::success([
            'profile' => $response->toArray(),
        ]);
    }
}
