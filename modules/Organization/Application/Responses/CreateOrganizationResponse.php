<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Responses;

use Modules\Organization\Domain\Aggregates\Organization;

final readonly class CreateOrganizationResponse
{
    private function __construct(
        public string $id,
        public string $name,
        public string $type,
    ) {}

    public static function fromOrganization(Organization $organization): self
    {
        return new self(
            id: $organization->id()->value(),
            name: $organization->name()->value(),
            type: $organization->type()->value,
        );
    }

    /**
     * @return array{id: string, name: string, type: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
        ];
    }
}
