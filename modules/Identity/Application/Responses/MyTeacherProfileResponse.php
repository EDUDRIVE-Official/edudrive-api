<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Responses;

final readonly class MyTeacherProfileResponse
{
    /**
     * @param  list<string>  $organizationIds
     * @param  list<array{id: string, course_id: string, name: string}>  $groups
     * @param  list<string>  $evaluationPermissions
     */
    public function __construct(
        public string $userId,
        public string $name,
        public ?string $specialties,
        public ?string $certifications,
        public array $organizationIds,
        public array $groups,
        public array $evaluationPermissions,
    ) {}

    /**
     * @return array{
     *     user_id: string,
     *     name: string,
     *     specialties: string|null,
     *     certifications: string|null,
     *     organization_ids: list<string>,
     *     groups: list<array{id: string, course_id: string, name: string}>,
     *     evaluation_permissions: list<string>
     * }
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'name' => $this->name,
            'specialties' => $this->specialties,
            'certifications' => $this->certifications,
            'organization_ids' => $this->organizationIds,
            'groups' => $this->groups,
            'evaluation_permissions' => $this->evaluationPermissions,
        ];
    }
}
