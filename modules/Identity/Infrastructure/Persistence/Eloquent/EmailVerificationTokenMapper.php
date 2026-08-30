<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use Modules\Identity\Domain\Entities\EmailVerificationToken;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\EmailVerificationTokenModel;

final class EmailVerificationTokenMapper
{
    public static function toDomain(EmailVerificationTokenModel $model): EmailVerificationToken
    {
        return EmailVerificationToken::reconstitute(
            email: Email::fromString($model->email),
            tokenHash: $model->token,
            createdAt: $model->created_at?->toDateTimeImmutable() ?? new DateTimeImmutable,
        );
    }

    /**
     * @return array<string, string|DateTimeImmutable>
     */
    public static function toPersistence(EmailVerificationToken $token): array
    {
        return [
            'email' => $token->email()->value(),
            'token' => $token->tokenHash(),
            'created_at' => $token->createdAt(),
        ];
    }
}
