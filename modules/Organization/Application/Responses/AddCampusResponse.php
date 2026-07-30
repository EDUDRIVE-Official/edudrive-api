<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Responses;

use Modules\Organization\Domain\Entities\Campus;

final readonly class AddCampusResponse
{
    private function __construct(
        public string $id,
        public string $name,
    ) {}

    public static function fromCampus(Campus $campus): self
    {
        return new self(
            id: $campus->id(),
            name: $campus->name(),
        );
    }

    /**
     * @return array{id: string, name: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
