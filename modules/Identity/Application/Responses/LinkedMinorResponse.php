<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Responses;

final readonly class LinkedMinorResponse
{
    public function __construct(
        public string $userId,
        public string $name,
    ) {}

    /** @return array{user_id: string, name: string} */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'name' => $this->name,
        ];
    }
}
