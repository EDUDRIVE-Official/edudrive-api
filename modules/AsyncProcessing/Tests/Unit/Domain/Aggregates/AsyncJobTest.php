<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Enums\AsyncJobStatus;
use Modules\AsyncProcessing\Domain\Exceptions\InvalidAsyncJobTransition;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;

function anAsyncJob(): AsyncJob
{
    return AsyncJob::request(
        id: AsyncJobId::fromString((string) Str::uuid()),
        type: 'export.enrollments',
        requestedByUserId: (string) Str::uuid(),
    );
}

it('se solicita en estado pendiente', function (): void {
    $job = anAsyncJob();

    expect($job->status())->toBe(AsyncJobStatus::Pending)
        ->and($job->result())->toBeNull()
        ->and($job->failureReason())->toBeNull()
        ->and($job->startedAt())->toBeNull()
        ->and($job->completedAt())->toBeNull();
});

it('pasa a procesando y luego a completado con un resultado', function (): void {
    $job = anAsyncJob();
    $startedAt = new DateTimeImmutable('2026-08-29T10:00:00+00:00');
    $completedAt = new DateTimeImmutable('2026-08-29T10:00:05+00:00');

    $job->start($startedAt);
    expect($job->status())->toBe(AsyncJobStatus::Processing)
        ->and($job->startedAt())->toBe($startedAt);

    $job->complete(['url' => 'https://example.test/file.csv', 'row_count' => 3], $completedAt);
    expect($job->status())->toBe(AsyncJobStatus::Completed)
        ->and($job->result())->toBe(['url' => 'https://example.test/file.csv', 'row_count' => 3])
        ->and($job->completedAt())->toBe($completedAt);
});

it('permite completar directamente desde pendiente sin pasar por procesando', function (): void {
    $job = anAsyncJob();

    $job->complete(['ok' => true], new DateTimeImmutable('now'));

    expect($job->status())->toBe(AsyncJobStatus::Completed);
});

it('falla desde pendiente o procesando con una razon', function (): void {
    $job = anAsyncJob();
    $job->start(new DateTimeImmutable('now'));

    $job->fail('el proveedor externo no respondio', new DateTimeImmutable('now'));

    expect($job->status())->toBe(AsyncJobStatus::Failed)
        ->and($job->failureReason())->toBe('el proveedor externo no respondio');
});

it('rechaza iniciar un trabajo que no esta pendiente', function (): void {
    $job = anAsyncJob();
    $job->start(new DateTimeImmutable('now'));

    expect(fn () => $job->start(new DateTimeImmutable('now')))
        ->toThrow(InvalidAsyncJobTransition::class);
});

it('rechaza completar o fallar un trabajo ya finalizado', function (): void {
    $job = anAsyncJob();
    $job->complete(['ok' => true], new DateTimeImmutable('now'));

    expect(fn () => $job->complete(['ok' => true], new DateTimeImmutable('now')))
        ->toThrow(InvalidAsyncJobTransition::class);

    expect(fn () => $job->fail('razon', new DateTimeImmutable('now')))
        ->toThrow(InvalidAsyncJobTransition::class);
});

it('restaura el agregado completo desde persistencia', function (): void {
    $id = AsyncJobId::fromString((string) Str::uuid());
    $createdAt = new DateTimeImmutable('2026-08-29T09:00:00+00:00');
    $startedAt = new DateTimeImmutable('2026-08-29T09:00:01+00:00');
    $completedAt = new DateTimeImmutable('2026-08-29T09:00:02+00:00');

    $job = AsyncJob::restore(
        id: $id,
        type: 'import.users',
        requestedByUserId: 'user-1',
        status: AsyncJobStatus::Failed,
        result: null,
        failureReason: 'fila invalida',
        createdAt: $createdAt,
        startedAt: $startedAt,
        completedAt: $completedAt,
    );

    expect($job->id()->equals($id))->toBeTrue()
        ->and($job->type())->toBe('import.users')
        ->and($job->requestedByUserId())->toBe('user-1')
        ->and($job->status())->toBe(AsyncJobStatus::Failed)
        ->and($job->failureReason())->toBe('fila invalida')
        ->and($job->createdAt())->toBe($createdAt)
        ->and($job->startedAt())->toBe($startedAt)
        ->and($job->completedAt())->toBe($completedAt);
});
