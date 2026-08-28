<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Models\AuditLogModel;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

it('elimina las cuentas inactivas mas alla del periodo de retencion configurado', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);

    $inactiveUser = User::register(
        id: (string) Str::uuid(),
        name: 'Cuenta inactiva',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    $inactiveUser->recordLogin(new DateTimeImmutable('-4 years'));
    $repository->save($inactiveUser);

    $activeUser = User::register(
        id: (string) Str::uuid(),
        name: 'Cuenta activa',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    $activeUser->recordLogin(new DateTimeImmutable('-1 month'));
    $repository->save($activeUser);

    $this->artisan('identity:purge-inactive-accounts')->assertSuccessful();

    expect(UserModel::query()->find($inactiveUser->id()))->toBeNull()
        ->and(UserModel::query()->find($activeUser->id()))->not->toBeNull();
});

it('audita cada eliminacion por retencion sin un actor http', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);

    $inactiveUser = User::register(
        id: (string) Str::uuid(),
        name: 'Cuenta inactiva',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    $inactiveUser->recordLogin(new DateTimeImmutable('-4 years'));
    $repository->save($inactiveUser);

    $this->artisan('identity:purge-inactive-accounts')->assertSuccessful();

    $entry = AuditLogModel::query()
        ->where('action', 'identity.account_deleted')
        ->where('entity_id', $inactiveUser->id())
        ->first();

    expect($entry)->not->toBeNull()
        ->and($entry->user_id)->toBeNull()
        ->and($entry->metadata['actor_id'] ?? null)->toBeNull();
});

it('no elimina cuentas cuyo ultimo inicio de sesion esta dentro del periodo de retencion', function (): void {
    /** @var TestCase $this */
    $repository = app(UserRepository::class);

    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Cuenta reciente',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    $user->recordLogin(new DateTimeImmutable('-2 years'));
    $repository->save($user);

    $this->artisan('identity:purge-inactive-accounts')->assertSuccessful();

    expect(UserModel::query()->find($user->id()))->not->toBeNull();
});
