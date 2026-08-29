<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Modules\Academic\Application\Commands\ExportEnrollmentsCommand;
use Modules\Academic\Application\UseCases\ExportEnrollmentsHandler;
use Modules\Academic\Infrastructure\Jobs\ExportEnrollmentsJob;
use Modules\AsyncProcessing\Application\Responses\AsyncJobResponse;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;

final class InMemoryAsyncJobRepositoryForEnrollmentsExport implements AsyncJobRepository
{
    /** @var array<string, AsyncJob> */
    public array $items = [];

    public function save(AsyncJob $job): void
    {
        $this->items[$job->id()->value()] = $job;
    }

    public function findById(AsyncJobId $id): ?AsyncJob
    {
        return $this->items[$id->value()] ?? null;
    }

    /** @return list<AsyncJob> */
    public function allCompletedOrFailedBefore(DateTimeImmutable $threshold): array
    {
        return [];
    }

    public function delete(AsyncJobId $id): void
    {
        unset($this->items[$id->value()]);
    }
}

it('crea un trabajo asincrono pendiente y despacha el job de exportacion de enrollments', function (): void {
    Queue::fake();
    $jobs = new InMemoryAsyncJobRepositoryForEnrollmentsExport;
    $handler = new ExportEnrollmentsHandler($jobs);

    $response = $handler->handle(new ExportEnrollmentsCommand(requestedByUserId: 'user-1'));

    expect($response)->toBeInstanceOf(AsyncJobResponse::class)
        ->and($response->type)->toBe('export.enrollments')
        ->and($response->status)->toBe('pending')
        ->and($jobs->items)->toHaveCount(1);

    Queue::assertPushed(ExportEnrollmentsJob::class, fn (ExportEnrollmentsJob $job): bool => $job->asyncJobId === $response->id);
});
