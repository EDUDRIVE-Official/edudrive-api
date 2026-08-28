<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Legal\Domain\Entities\UserConsent;
use Modules\Legal\Domain\Repositories\UserConsentRepository;
use Modules\Legal\Domain\ValueObjects\PolicyKey;

function persistedConsentUserId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario de consentimiento',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user->id();
}

it('guarda y recupera el historial de consentimientos de un usuario', function (): void {
    $repository = app(UserConsentRepository::class);
    $userId = persistedConsentUserId();

    $repository->save(UserConsent::accept(
        id: (string) Str::uuid(),
        userId: $userId,
        policyKey: PolicyKey::fromString('privacy_policy'),
        policyVersion: 1,
    ));
    $repository->save(UserConsent::accept(
        id: (string) Str::uuid(),
        userId: $userId,
        policyKey: PolicyKey::fromString('terms_of_service'),
        policyVersion: 1,
    ));

    expect($repository->findByUserId($userId))->toHaveCount(2);
});

it('no devuelve consentimientos de otros usuarios', function (): void {
    $repository = app(UserConsentRepository::class);

    $repository->save(UserConsent::accept(
        id: (string) Str::uuid(),
        userId: persistedConsentUserId(),
        policyKey: PolicyKey::fromString('privacy_policy'),
        policyVersion: 1,
    ));

    expect($repository->findByUserId(persistedConsentUserId()))->toBe([]);
});

it('elimina en cascada el historial de consentimientos al eliminar el usuario', function (): void {
    $repository = app(UserConsentRepository::class);
    $userId = persistedConsentUserId();

    $repository->save(UserConsent::accept(
        id: (string) Str::uuid(),
        userId: $userId,
        policyKey: PolicyKey::fromString('privacy_policy'),
        policyVersion: 1,
    ));

    UserModel::query()->whereKey($userId)->delete();

    expect($repository->findByUserId($userId))->toBe([]);
});
