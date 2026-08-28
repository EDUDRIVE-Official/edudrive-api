<?php

declare(strict_types=1);

namespace Modules\Legal\Application\Responses;

use DateTimeInterface;
use Modules\Legal\Domain\Entities\UserConsent;

final readonly class ConsentResponse
{
    public function __construct(
        public string $id,
        public string $policyKey,
        public int $policyVersion,
        public string $acceptedAt,
    ) {}

    public static function fromUserConsent(UserConsent $consent): self
    {
        return new self(
            id: $consent->id(),
            policyKey: $consent->policyKey()->value(),
            policyVersion: $consent->policyVersion(),
            acceptedAt: $consent->acceptedAt()->format(DateTimeInterface::ATOM),
        );
    }

    /** @return array{id: string, policy_key: string, policy_version: int, accepted_at: string} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'policy_key' => $this->policyKey,
            'policy_version' => $this->policyVersion,
            'accepted_at' => $this->acceptedAt,
        ];
    }
}
