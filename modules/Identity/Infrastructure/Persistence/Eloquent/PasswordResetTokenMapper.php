<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use Modules\Identity\Domain\Entities\PasswordResetToken;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\PasswordResetTokenModel;

final class PasswordResetTokenMapper
{
    public static function toDomain(PasswordResetTokenModel $model): PasswordResetToken
    {
        return PasswordResetToken::reconstitute(
            email: Email::fromString($model->email),
            tokenHash: $model->token,
            createdAt: $model->created_at?->toDateTimeImmutable() ?? new DateTimeImmutable,
        );
    }

    /**
     * @return array<string, string|DateTimeImmutable>
     */
    public static function toPersistence(PasswordResetToken $token): array
    {
        return [
            'email' => $token->email()->value(),
            'token' => $token->tokenHash(),
            'created_at' => $token->createdAt(),
        ];
    }
}
