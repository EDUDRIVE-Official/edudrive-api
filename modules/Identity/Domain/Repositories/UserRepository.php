<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Repositories;

use DateTimeImmutable;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\ValueObjects\Email;

interface UserRepository
{
    public function save(User $user): void;

    public function findById(string $id): ?User;

    public function findByEmail(Email $email): ?User;

    public function existsByEmail(Email $email): bool;

    public function delete(string $id): void;

    /** @return list<User> */
    public function all(): array;

    /** @return list<User> */
    public function findInactiveBefore(DateTimeImmutable $threshold): array;
}
