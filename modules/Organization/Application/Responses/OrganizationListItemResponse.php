<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Responses;

use Modules\Organization\Domain\Aggregates\Organization;

final readonly class OrganizationListItemResponse
{
    /**
     * @param  list<array{id: string, name: string}>  $campuses
     */
    private function __construct(
        public string $id,
        public string $name,
        public string $type,
        public array $campuses,
    ) {}

    public static function fromOrganization(Organization $organization): self
    {
        return new self(
            id: $organization->id()->value(),
            name: $organization->name()->value(),
            type: $organization->type()->value,
            campuses: array_map(
                static fn ($campus): array => [
                    'id' => $campus->id(),
                    'name' => $campus->name(),
                ],
                $organization->campuses(),
            ),
        );
    }

    /**
     * @return array{id: string, name: string, type: string, campuses: list<array{id: string, name: string}>}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'campuses' => $this->campuses,
        ];
    }
}
