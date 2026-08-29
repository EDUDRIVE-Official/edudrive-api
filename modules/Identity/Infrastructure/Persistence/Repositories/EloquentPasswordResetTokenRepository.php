<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Persistence\Repositories;

use Modules\Identity\Domain\Entities\PasswordResetToken;
use Modules\Identity\Domain\Repositories\PasswordResetTokenRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\PasswordResetTokenModel;
use Modules\Identity\Infrastructure\Persistence\Eloquent\PasswordResetTokenMapper;

final class EloquentPasswordResetTokenRepository implements PasswordResetTokenRepository
{
    public function save(PasswordResetToken $token): void
    {
        PasswordResetTokenModel::query()->updateOrCreate(
            ['email' => $token->email()->value()],
            PasswordResetTokenMapper::toPersistence($token),
        );
    }

    public function findByEmail(Email $email): ?PasswordResetToken
    {
        $model = PasswordResetTokenModel::query()->find($email->value());

        return $model instanceof PasswordResetTokenModel
            ? PasswordResetTokenMapper::toDomain($model)
            : null;
    }

    public function deleteByEmail(Email $email): void
    {
        PasswordResetTokenModel::query()->whereKey($email->value())->delete();
    }
}
