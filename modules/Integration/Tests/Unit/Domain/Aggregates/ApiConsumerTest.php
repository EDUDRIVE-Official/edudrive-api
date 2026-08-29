<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Integration\Domain\Aggregates\ApiConsumer;
use Modules\Integration\Domain\Enums\ApiConsumerStatus;
use Modules\Integration\Domain\Exceptions\InvalidApiConsumerTransition;
use Modules\Integration\Domain\ValueObjects\ApiConsumerHistoryEntry;
use Modules\Integration\Domain\ValueObjects\ApiConsumerId;
use Modules\Integration\Domain\ValueObjects\IntegrationKey;

function newApiConsumer(?DateTimeImmutable $expiresAt = null): ApiConsumer
{
    return ApiConsumer::register(
        id: ApiConsumerId::fromString((string) Str::uuid()),
        name: 'Sistema externo de reportes',
        scopes: ['reports.view'],
        integrationKey: IntegrationKey::generate(),
        expiresAt: $expiresAt,
        createdAt: new DateTimeImmutable('2026-08-29T10:00:00+00:00'),
    );
}

it('se registra en estado active y sin historial', function (): void {
    $consumer = newApiConsumer();

    expect($consumer->status())->toBe(ApiConsumerStatus::Active)
        ->and($consumer->history())->toBe([])
        ->and($consumer->scopes())->toBe(['reports.view']);
});

it('esta disponible en cualquier momento cuando no tiene expiracion', function (): void {
    $consumer = newApiConsumer();

    expect($consumer->isUsableAt(new DateTimeImmutable('2099-01-01T00:00:00+00:00')))->toBeTrue();
});

it('deja de estar disponible despues de su fecha de expiracion', function (): void {
    $consumer = newApiConsumer(new DateTimeImmutable('2026-09-01T00:00:00+00:00'));

    expect($consumer->isUsableAt(new DateTimeImmutable('2026-08-30T00:00:00+00:00')))->toBeTrue()
        ->and($consumer->isUsableAt(new DateTimeImmutable('2026-09-02T00:00:00+00:00')))->toBeFalse();
});

it('verifica si tiene un alcance concreto', function (): void {
    $consumer = newApiConsumer();

    expect($consumer->hasScope('reports.view'))->toBeTrue()
        ->and($consumer->hasScope('users.manage'))->toBeFalse();
});

it('suspende un consumidor activo y registra el cambio en el historial', function (): void {
    $consumer = newApiConsumer();

    $consumer->suspend('Uso indebido detectado', new DateTimeImmutable('2026-08-30T00:00:00+00:00'));

    expect($consumer->status())->toBe(ApiConsumerStatus::Suspended)
        ->and($consumer->isUsableAt(new DateTimeImmutable('2026-08-30T00:00:00+00:00')))->toBeFalse()
        ->and($consumer->history())->toHaveCount(1);

    $entry = $consumer->history()[0];
    expect($entry->fromStatus)->toBe(ApiConsumerStatus::Active)
        ->and($entry->toStatus)->toBe(ApiConsumerStatus::Suspended)
        ->and($entry->reason)->toBe('Uso indebido detectado');
});

it('rechaza suspender un consumidor que ya esta suspendido', function (): void {
    $consumer = newApiConsumer();
    $consumer->suspend(null, new DateTimeImmutable('now'));

    expect(fn () => $consumer->suspend(null, new DateTimeImmutable('now')))
        ->toThrow(InvalidApiConsumerTransition::class);
});

it('rechaza suspender un consumidor revocado', function (): void {
    $consumer = newApiConsumer();
    $consumer->revoke(null, new DateTimeImmutable('now'));

    expect(fn () => $consumer->suspend(null, new DateTimeImmutable('now')))
        ->toThrow(InvalidApiConsumerTransition::class);
});

it('reactiva un consumidor suspendido', function (): void {
    $consumer = newApiConsumer();
    $consumer->suspend(null, new DateTimeImmutable('now'));

    $consumer->reactivate(new DateTimeImmutable('2026-08-31T00:00:00+00:00'));

    expect($consumer->status())->toBe(ApiConsumerStatus::Active)
        ->and($consumer->history())->toHaveCount(2);
});

it('rechaza reactivar un consumidor activo', function (): void {
    $consumer = newApiConsumer();

    expect(fn () => $consumer->reactivate(new DateTimeImmutable('now')))
        ->toThrow(InvalidApiConsumerTransition::class);
});

it('rechaza reactivar un consumidor revocado', function (): void {
    $consumer = newApiConsumer();
    $consumer->revoke(null, new DateTimeImmutable('now'));

    expect(fn () => $consumer->reactivate(new DateTimeImmutable('now')))
        ->toThrow(InvalidApiConsumerTransition::class);
});

it('revoca un consumidor activo o suspendido de forma terminal', function (): void {
    $activeConsumer = newApiConsumer();
    $activeConsumer->revoke('Integracion descontinuada', new DateTimeImmutable('now'));
    expect($activeConsumer->status())->toBe(ApiConsumerStatus::Revoked);

    $suspendedConsumer = newApiConsumer();
    $suspendedConsumer->suspend(null, new DateTimeImmutable('now'));
    $suspendedConsumer->revoke(null, new DateTimeImmutable('now'));
    expect($suspendedConsumer->status())->toBe(ApiConsumerStatus::Revoked);
});

it('rechaza revocar un consumidor ya revocado', function (): void {
    $consumer = newApiConsumer();
    $consumer->revoke(null, new DateTimeImmutable('now'));

    expect(fn () => $consumer->revoke(null, new DateTimeImmutable('now')))
        ->toThrow(InvalidApiConsumerTransition::class);
});

it('rota la llave de integracion sin registrar historial', function (): void {
    $consumer = newApiConsumer();
    $originalHash = $consumer->integrationKey()->hash();
    $newKey = IntegrationKey::generate();

    $consumer->rotateIntegrationKey($newKey);

    expect($consumer->integrationKey()->hash())->toBe($newKey->hash())
        ->and($consumer->integrationKey()->hash())->not->toBe($originalHash)
        ->and($consumer->history())->toBe([]);
});

it('restaura el agregado completo desde persistencia', function (): void {
    $id = ApiConsumerId::fromString((string) Str::uuid());
    $integrationKey = IntegrationKey::fromHash(hash('sha256', 'valor-secreto'));
    $createdAt = new DateTimeImmutable('2026-08-29T10:00:00+00:00');
    $expiresAt = new DateTimeImmutable('2027-08-29T10:00:00+00:00');
    $historyEntry = ApiConsumerHistoryEntry::restore(
        ApiConsumerStatus::Active,
        ApiConsumerStatus::Suspended,
        new DateTimeImmutable('2026-08-30T00:00:00+00:00'),
        'Motivo',
    );

    $consumer = ApiConsumer::restore(
        id: $id,
        name: 'Sistema externo',
        scopes: ['reports.view', 'users.view'],
        status: ApiConsumerStatus::Suspended,
        integrationKey: $integrationKey,
        expiresAt: $expiresAt,
        createdAt: $createdAt,
        history: [$historyEntry],
    );

    expect($consumer->id()->equals($id))->toBeTrue()
        ->and($consumer->name())->toBe('Sistema externo')
        ->and($consumer->scopes())->toBe(['reports.view', 'users.view'])
        ->and($consumer->status())->toBe(ApiConsumerStatus::Suspended)
        ->and($consumer->integrationKey()->hash())->toBe($integrationKey->hash())
        ->and($consumer->expiresAt())->toBe($expiresAt)
        ->and($consumer->createdAt())->toBe($createdAt)
        ->and($consumer->history())->toBe([$historyEntry]);
});
