<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Responses;

use Modules\Identity\Domain\Entities\GuardianRelationship;

final readonly class GuardianRelationshipResponse
{
    public function __construct(
        public string $id,
        public string $guardianUserId,
        public string $minorUserId,
        public string $createdAt,
        public ?string $revokedAt,
        public bool $isActive,
    ) {}

    public static function fromRelationship(GuardianRelationship $relationship): self
    {
        return new self(
            id: $relationship->id(),
            guardianUserId: $relationship->guardianUserId(),
            minorUserId: $relationship->minorUserId(),
            createdAt: $relationship->createdAt()->format(DATE_ATOM),
            revokedAt: $relationship->revokedAt()?->format(DATE_ATOM),
            isActive: $relationship->isActive(),
        );
    }

    /**
     * @return array{
     *     id: string,
     *     guardian_user_id: string,
     *     minor_user_id: string,
     *     created_at: string,
     *     revoked_at: string|null,
     *     is_active: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'guardian_user_id' => $this->guardianUserId,
            'minor_user_id' => $this->minorUserId,
            'created_at' => $this->createdAt,
            'revoked_at' => $this->revokedAt,
            'is_active' => $this->isActive,
        ];
    }
}
