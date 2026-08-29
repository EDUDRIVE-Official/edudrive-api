<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Integration\Domain\Aggregates\ApiConsumer;
use Modules\Integration\Domain\Enums\ApiConsumerStatus;
use Modules\Integration\Domain\Repositories\ApiConsumerRepository;
use Modules\Integration\Domain\ValueObjects\ApiConsumerId;
use Modules\Integration\Domain\ValueObjects\IntegrationKey;
use Modules\Integration\Infrastructure\Persistence\Eloquent\Models\ApiConsumerHistoryEntryModel;
use Modules\Integration\Infrastructure\Persistence\Eloquent\Models\ApiConsumerModel;

uses(RefreshDatabase::class);

function newPersistableApiConsumer(?DateTimeImmutable $expiresAt = null): ApiConsumer
{
    return ApiConsumer::register(
        id: ApiConsumerId::fromString((string) Str::uuid()),
        name: 'Sistema externo de reportes',
        scopes: ['reports.view', 'users.view'],
        integrationKey: IntegrationKey::generate(),
        expiresAt: $expiresAt,
        createdAt: new DateTimeImmutable('2026-08-29T10:00:00+00:00'),
    );
}

it('guarda y recupera un consumidor por identificador', function (): void {
    $consumer = newPersistableApiConsumer();

    app(ApiConsumerRepository::class)->save($consumer);
    $found = app(ApiConsumerRepository::class)->findById($consumer->id());

    expect($found)->not->toBeNull()
        ->and($found?->id()->equals($consumer->id()))->toBeTrue()
        ->and($found?->name())->toBe('Sistema externo de reportes')
        ->and($found?->scopes())->toBe(['reports.view', 'users.view'])
        ->and($found?->status())->toBe(ApiConsumerStatus::Active)
        ->and($found?->integrationKey()->hash())->toBe($consumer->integrationKey()->hash())
        ->and($found?->history())->toBe([]);
});

it('guarda y recupera la expiracion y el historial', function (): void {
    $consumer = newPersistableApiConsumer(new DateTimeImmutable('2027-08-29T10:00:00+00:00'));
    $consumer->suspend('Uso indebido', new DateTimeImmutable('2026-08-30T00:00:00+00:00'));
    app(ApiConsumerRepository::class)->save($consumer);

    $found = app(ApiConsumerRepository::class)->findById($consumer->id());

    expect($found?->expiresAt()?->format(DATE_ATOM))->toBe('2027-08-29T10:00:00+00:00')
        ->and($found?->status())->toBe(ApiConsumerStatus::Suspended)
        ->and($found?->history())->toHaveCount(1)
        ->and($found?->history()[0]->reason)->toBe('Uso indebido');
});

it('encuentra un consumidor por el hash de la llave de integracion', function (): void {
    $consumer = newPersistableApiConsumer();
    app(ApiConsumerRepository::class)->save($consumer);

    $found = app(ApiConsumerRepository::class)->findByIntegrationKeyHash($consumer->integrationKey()->hash());

    expect($found?->id()->equals($consumer->id()))->toBeTrue();
    expect(app(ApiConsumerRepository::class)->findByIntegrationKeyHash('hash-inexistente'))->toBeNull();
});

it('lista todos los consumidores registrados', function (): void {
    $repository = app(ApiConsumerRepository::class);
    $repository->save(newPersistableApiConsumer());
    $repository->save(newPersistableApiConsumer());
    $repository->save(newPersistableApiConsumer());

    expect($repository->all())->toHaveCount(3);
});

it('reemplaza el historial en vez de duplicarlo al guardar de nuevo', function (): void {
    $repository = app(ApiConsumerRepository::class);
    $consumer = newPersistableApiConsumer();
    $consumer->suspend(null, new DateTimeImmutable('now'));
    $repository->save($consumer);
    $repository->save($consumer);

    $found = $repository->findById($consumer->id());

    expect($found?->history())->toHaveCount(1);
});

it('borra en cascada el historial al eliminar el consumidor', function (): void {
    $repository = app(ApiConsumerRepository::class);
    $consumer = newPersistableApiConsumer();
    $consumer->suspend(null, new DateTimeImmutable('now'));
    $repository->save($consumer);

    ApiConsumerModel::query()->where('id', $consumer->id()->value())->delete();

    expect(ApiConsumerHistoryEntryModel::query()->where('api_consumer_id', $consumer->id()->value())->count())->toBe(0);
});
