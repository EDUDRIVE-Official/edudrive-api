<?php

declare(strict_types=1);

namespace Modules\Identity\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Academic\Application\Services\LearnerStageResolver;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Infrastructure\Persistence\Eloquent\Models\RoleAssignmentModel;
use Modules\Identity\Application\Commands\UpdateStudentProfileCommand;
use Modules\Identity\Application\Commands\UpdateUserDateOfBirthCommand;
use Modules\Identity\Application\Queries\GetMyStudentProfileQuery;
use Modules\Identity\Application\UseCases\GetMyStudentProfileHandler;
use Modules\Identity\Application\UseCases\UpdateStudentProfileHandler;
use Modules\Identity\Application\UseCases\UpdateUserDateOfBirthHandler;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\GuardianRelationshipModel;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\OrganizationMembershipRequestModel;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Identity\Presentation\Http\Requests\UpdateStudentProfileRequest;
use Modules\Organization\Infrastructure\Persistence\Eloquent\Models\OrganizationModel;

final class StudentProfileWebController extends Controller
{
    public function __construct(
        private readonly GetMyStudentProfileHandler $getProfile,
        private readonly UpdateStudentProfileHandler $updateProfile,
        private readonly UpdateUserDateOfBirthHandler $updateDateOfBirth,
        private readonly CourseRepository $courses,
    ) {}

    public function show(Request $request): View
    {
        $userId = (string) $request->user()?->getAuthIdentifier();

        $profile = $this->getProfile->handle(
            new GetMyStudentProfileQuery(userId: $userId),
        );

        $data = $profile->toArray();
        $learner = $request->user();
        $data['curricular_route'] = app(LearnerStageResolver::class)->resolve($learner instanceof UserModel ? $learner->date_of_birth?->toDateTimeImmutable() : null);
        $courseNames = [];
        foreach ($this->courses->all() as $course) {
            $courseNames[$course->id()->value()] = $course->title()->value();
        }
        $data['enrollments'] = array_map(static fn (array $enrollment): array => array_merge($enrollment, [
            'course_title' => $courseNames[$enrollment['course_id']] ?? 'Curso de educación vial',
        ]), $data['enrollments']);

        $guardianIds = GuardianRelationshipModel::query()
            ->where('minor_user_id', $userId)
            ->whereNull('revoked_at')
            ->pluck('guardian_user_id');
        $minorIds = GuardianRelationshipModel::query()
            ->where('guardian_user_id', $userId)
            ->whereNull('revoked_at')
            ->pluck('minor_user_id');
        $data['guardians'] = UserModel::query()->whereIn('id', $guardianIds)->orderBy('name')->pluck('name')->all();
        $data['linked_minors'] = UserModel::query()->whereIn('id', $minorIds)->orderBy('name')->pluck('name')->all();

        $memberOrganizationIds = RoleAssignmentModel::query()->where('user_id', $userId)
            ->where('role', Role::Student->value)->whereNotNull('organization_id')->pluck('organization_id');
        $data['organizations'] = OrganizationModel::query()->whereIn('id', $memberOrganizationIds)->orderBy('name')->get(['id', 'name', 'type'])->toArray();
        $data['organization_requests'] = OrganizationMembershipRequestModel::query()
            ->where('user_id', $userId)->where('status', 'pending')->orderByDesc('requested_at')->get()->map(fn (OrganizationMembershipRequestModel $item): array => [
                'id' => (string) $item->id,
                'organization_id' => (string) $item->organization_id,
                'organization_name' => OrganizationModel::query()->whereKey($item->organization_id)->value('name') ?? 'Organización',
                'requested_at' => $item->requested_at->format('d/m/Y'),
            ])->all();
        $pendingOrganizationIds = collect($data['organization_requests'])->pluck('organization_id');
        $data['available_organizations'] = OrganizationModel::query()->whereNotIn('id', $memberOrganizationIds)
            ->whereNotIn('id', $pendingOrganizationIds)->orderBy('name')->get(['id', 'name', 'type'])->toArray();

        return view('profile.show', ['profile' => $data]);
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
                learningPurpose: $data['learning_purpose'] ?? null,
                updateLearningPurpose: array_key_exists('learning_purpose', $data),
            ),
        );

        if (array_key_exists('date_of_birth', $data)) {
            $this->updateDateOfBirth->handle(new UpdateUserDateOfBirthCommand(
                userId: (string) $request->user()?->getAuthIdentifier(),
                dateOfBirth: isset($data['date_of_birth']) ? (string) $data['date_of_birth'] : null,
            ));
        }

        return redirect()
            ->route('student-profile.show')
            ->with('status', 'Perfil actualizado correctamente.');
    }
}
