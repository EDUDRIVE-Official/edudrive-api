<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use Modules\Academic\Domain\Aggregates\Group;
use Modules\Academic\Domain\Repositories\GroupRepository;
use Modules\Authorization\Domain\Entities\RoleAssignment;
use Modules\Authorization\Domain\Enums\Permission;
use Modules\Authorization\Domain\Enums\Role;
use Modules\Authorization\Domain\Repositories\RoleAssignmentRepository;
use Modules\Authorization\Domain\Services\RolePermissions;
use Modules\Identity\Application\Queries\GetMyTeacherProfileQuery;
use Modules\Identity\Application\Responses\MyTeacherProfileResponse;
use Modules\Identity\Domain\Exceptions\UserNotFound;
use Modules\Identity\Domain\Repositories\TeacherProfileRepository;
use Modules\Identity\Domain\Repositories\UserRepository;

final readonly class GetMyTeacherProfileHandler
{
    /** @var list<Permission> */
    private const EVALUATION_PERMISSIONS = [
        Permission::ViewExams,
        Permission::ManageExams,
        Permission::ViewExamAttempts,
        Permission::ViewQuestions,
        Permission::ManageQuestions,
    ];

    public function __construct(
        private UserRepository $users,
        private TeacherProfileRepository $profiles,
        private RoleAssignmentRepository $roleAssignments,
        private GroupRepository $groups,
    ) {}

    public function handle(GetMyTeacherProfileQuery $query): MyTeacherProfileResponse
    {
        $user = $this->users->findById($query->userId);

        if ($user === null) {
            throw new UserNotFound;
        }

        $profile = $this->profiles->findByUserId($query->userId);
        $assignments = $this->roleAssignments->findByUserId($query->userId);

        $organizationIds = array_values(array_unique(array_filter(array_map(
            static fn (RoleAssignment $assignment): ?string => $assignment->role() === Role::Teacher
                ? $assignment->organizationId()
                : null,
            $assignments,
        ))));

        $evaluationPermissions = [];
        foreach ($assignments as $assignment) {
            foreach (self::EVALUATION_PERMISSIONS as $permission) {
                if (RolePermissions::grants($assignment->role(), $permission)) {
                    $evaluationPermissions[] = $permission->value;
                }
            }
        }

        return new MyTeacherProfileResponse(
            userId: $user->id(),
            name: $user->name(),
            specialties: $profile?->specialties(),
            certifications: $profile?->certifications(),
            organizationIds: $organizationIds,
            groups: array_map(
                static fn (Group $group): array => [
                    'id' => $group->id()->value(),
                    'course_id' => $group->courseId()->value(),
                    'name' => $group->name(),
                ],
                $this->groups->all(teacherId: $query->userId),
            ),
            evaluationPermissions: array_values(array_unique($evaluationPermissions)),
        );
    }
}
