<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Persistence\Repositories;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Identity\Infrastructure\Persistence\Eloquent\UserMapper;

final class EloquentUserRepository implements UserRepository
{
    public function save(User $user): void
    {
        UserModel::query()->updateOrCreate(
            ['id' => $user->id()],
            UserMapper::toPersistence($user),
        );
    }

    public function findById(string $id): ?User
    {
        $model = UserModel::query()->find($id);

        return $model instanceof UserModel
            ? UserMapper::toDomain($model)
            : null;
    }

    public function findByEmail(Email $email): ?User
    {
        $model = UserModel::query()
            ->where('email', $email->value())
            ->first();

        return $model instanceof UserModel
            ? UserMapper::toDomain($model)
            : null;
    }

    public function existsByEmail(Email $email): bool
    {
        return UserModel::query()
            ->where('email', $email->value())
            ->exists();
    }

    public function delete(string $id): void
    {
        UserModel::query()->whereKey($id)->delete();
    }

    /** @return list<User> */
    public function all(): array
    {
        return array_values(
            UserModel::query()
                ->orderBy('created_at')
                ->get()
                ->map(fn (UserModel $model): User => UserMapper::toDomain($model))
                ->all(),
        );
    }

    /** @return list<User> */
    public function findInactiveBefore(DateTimeImmutable $threshold): array
    {
        return array_values(
            UserModel::query()
                ->where(function (Builder $query) use ($threshold): void {
                    $query->where('last_login_at', '<', $threshold)
                        ->orWhere(function (Builder $query) use ($threshold): void {
                            $query->whereNull('last_login_at')->where('created_at', '<', $threshold);
                        });
                })
                ->orderBy('created_at')
                ->get()
                ->map(fn (UserModel $model): User => UserMapper::toDomain($model))
                ->all(),
        );
    }
}
