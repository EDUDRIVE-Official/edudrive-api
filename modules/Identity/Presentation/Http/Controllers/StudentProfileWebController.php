<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Identity\Application\Commands\UpdateStudentProfileCommand;
use Modules\Identity\Application\Queries\GetMyStudentProfileQuery;
use Modules\Identity\Application\UseCases\GetMyStudentProfileHandler;
use Modules\Identity\Application\UseCases\UpdateStudentProfileHandler;
use Modules\Identity\Presentation\Http\Requests\UpdateStudentProfileRequest;

final class StudentProfileWebController extends Controller
{
    public function __construct(
        private readonly GetMyStudentProfileHandler $getProfile,
        private readonly UpdateStudentProfileHandler $updateProfile,
    ) {}

    public function show(Request $request): View
    {
        $userId = (string) $request->user()?->getAuthIdentifier();

        $profile = $this->getProfile->handle(
            new GetMyStudentProfileQuery(userId: $userId),
        );

        return view('profile.show', [
            'profile' => $profile->toArray(),
        ]);
    }

    public function update(UpdateStudentProfileRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->updateProfile->handle(
            new UpdateStudentProfileCommand(
                userId: (string) $request->user()?->getAuthIdentifier(),
                educationLevel: $data['education_level'] ?? null,
                accessibilityNeeds: $data['accessibility_needs'] ?? null,
                learningPreferences: $data['learning_preferences'] ?? null,
            ),
        );

        return redirect()
            ->route('student-profile.show')
            ->with('status', 'Perfil actualizado correctamente.');
    }
}
