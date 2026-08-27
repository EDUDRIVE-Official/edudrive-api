<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Responses;

use DateTimeInterface;
use Modules\Identity\Domain\Entities\User;

final readonly class UserResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $status,
        public ?string $emailVerifiedAt,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromUser(User $user): self
    {
        return new self(
            id: $user->id(),
            name: $user->name(),
            email: $user->email()->value(),
            status: $user->status()->value,
            emailVerifiedAt: $user->emailVerifiedAt()?->format(DateTimeInterface::ATOM),
            createdAt: $user->createdAt()->format(DateTimeInterface::ATOM),
            updatedAt: $user->updatedAt()->format(DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'email_verified_at' => $this->emailVerifiedAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
