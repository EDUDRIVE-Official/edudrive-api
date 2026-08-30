<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

use Modules\Academic\Domain\Aggregates\Group;

final readonly class GroupResponse
{
    private function __construct(
        public string $id,
        public string $courseId,
        public ?string $organizationId,
        public string $name,
        public ?string $teacherId,
        public string $startsAt,
        public string $endsAt,
    ) {}

    public static function fromGroup(Group $group): self
    {
        return new self(
            $group->id()->value(),
            $group->courseId()->value(),
            $group->organizationId(),
            $group->name(),
            $group->teacherId(),
            $group->startsAt()->format(DATE_ATOM),
            $group->endsAt()->format(DATE_ATOM),
        );
    }

    /**
     * @return array{
     *     id: string,
     *     course_id: string,
     *     organization_id: string|null,
     *     name: string,
     *     teacher_id: string|null,
     *     starts_at: string,
     *     ends_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->courseId,
            'organization_id' => $this->organizationId,
            'name' => $this->name,
            'teacher_id' => $this->teacherId,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
        ];
    }
}
