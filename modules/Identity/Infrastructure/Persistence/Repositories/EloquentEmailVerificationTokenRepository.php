<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Persistence\Repositories;

use Modules\Identity\Domain\Entities\EmailVerificationToken;
use Modules\Identity\Domain\Repositories\EmailVerificationTokenRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\EmailVerificationTokenMapper;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\EmailVerificationTokenModel;

final class EloquentEmailVerificationTokenRepository implements EmailVerificationTokenRepository
{
    public function save(EmailVerificationToken $token): void
    {
        EmailVerificationTokenModel::query()->updateOrCreate(
            ['email' => $token->email()->value()],
            EmailVerificationTokenMapper::toPersistence($token),
        );
    }

    public function findByEmail(Email $email): ?EmailVerificationToken
    {
        $model = EmailVerificationTokenModel::query()->find($email->value());

        return $model instanceof EmailVerificationTokenModel
            ? EmailVerificationTokenMapper::toDomain($model)
            : null;
    }

    public function deleteByEmail(Email $email): void
    {
        EmailVerificationTokenModel::query()->whereKey($email->value())->delete();
    }
}
