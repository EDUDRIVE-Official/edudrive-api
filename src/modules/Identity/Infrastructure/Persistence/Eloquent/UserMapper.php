<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Enums\UserStatus;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;

final class UserMapper
{
    public static function toDomain(UserModel $model): User
    {
        return User::reconstitute(
            id: $model->id,
            name: $model->name,
            email: Email::fromString($model->email),
            passwordHash: $model->password,
            status: UserStatus::from($model->status),
            emailVerifiedAt: $model->email_verified_at?->toDateTimeImmutable(),
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable(),
        );
    }

    /**
     * @return array<string, string|DateTimeImmutable|null>
     */
    public static function toPersistence(User $user): array
    {
        return [
            'id' => $user->id(),
            'name' => $user->name(),
            'email' => $user->email()->value(),
            'password' => $user->passwordHash(),
            'status' => $user->status()->value,
            'email_verified_at' => $user->emailVerifiedAt(),
            'created_at' => $user->createdAt(),
            'updated_at' => $user->updatedAt(),
        ];
    }
}
