<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Enums\AsyncJobStatus;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;

uses(RefreshDatabase::class);

function newPersistableAsyncJob(): AsyncJob
{
    return AsyncJob::request(
        id: AsyncJobId::fromString((string) Str::uuid()),
        type: 'export.enrollments',
        requestedByUserId: (string) Str::uuid(),
        createdAt: new DateTimeImmutable('2026-08-29T10:00:00+00:00'),
    );
}

it('guarda y recupera un trabajo asincrono pendiente', function (): void {
    $job = newPersistableAsyncJob();

    app(AsyncJobRepository::class)->save($job);
    $found = app(AsyncJobRepository::class)->findById($job->id());

    expect($found)->not->toBeNull()
        ->and($found?->id()->equals($job->id()))->toBeTrue()
        ->and($found?->type())->toBe('export.enrollments')
        ->and($found?->requestedByUserId())->toBe($job->requestedByUserId())
        ->and($found?->status())->toBe(AsyncJobStatus::Pending)
        ->and($found?->result())->toBeNull()
        ->and($found?->createdAt()->format(DateTimeInterface::ATOM))->toBe('2026-08-29T10:00:00+00:00');
});

it('guarda y recupera el resultado tras completar el trabajo', function (): void {
    $job = newPersistableAsyncJob();
    $job->start(new DateTimeImmutable('2026-08-29T10:00:01+00:00'));
    $job->complete(['url' => 'https://example.test/f.csv', 'row_count' => 5], new DateTimeImmutable('2026-08-29T10:00:02+00:00'));

    app(AsyncJobRepository::class)->save($job);
    $found = app(AsyncJobRepository::class)->findById($job->id());

    expect($found?->status())->toBe(AsyncJobStatus::Completed)
        ->and($found?->result())->toBe(['url' => 'https://example.test/f.csv', 'row_count' => 5])
        ->and($found?->startedAt())->not->toBeNull()
        ->and($found?->completedAt())->not->toBeNull();
});

it('guarda y recupera la razon de fallo', function (): void {
    $job = newPersistableAsyncJob();
    $job->fail('el proveedor externo no respondio', new DateTimeImmutable('now'));

    app(AsyncJobRepository::class)->save($job);
    $found = app(AsyncJobRepository::class)->findById($job->id());

    expect($found?->status())->toBe(AsyncJobStatus::Failed)
        ->and($found?->failureReason())->toBe('el proveedor externo no respondio');
});

it('retorna null para un identificador inexistente', function (): void {
    expect(app(AsyncJobRepository::class)->findById(AsyncJobId::fromString((string) Str::uuid())))->toBeNull();
});
