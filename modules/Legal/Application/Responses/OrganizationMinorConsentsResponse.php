<?php

declare(strict_types=1);

namespace Modules\Legal\Application\Responses;

final readonly class OrganizationMinorConsentsResponse
{
    /** @param list<ConsentResponse> $consents */
    public function __construct(
        public string $userId,
        public string $name,
        public array $consents,
    ) {}

    /** @return array{user_id: string, name: string, consents: list<array<string, mixed>>} */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'name' => $this->name,
            'consents' => array_map(
                static fn (ConsentResponse $consent): array => $consent->toArray(),
                $this->consents,
            ),
        ];
    }
}
