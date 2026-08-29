<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Modules\Academic\Application\Commands\ExportCoursesCommand;
use Modules\Academic\Application\UseCases\ExportCoursesHandler;
use Modules\Academic\Infrastructure\Jobs\ExportCoursesJob;
use Modules\AsyncProcessing\Application\Responses\AsyncJobResponse;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;

final class InMemoryAsyncJobRepositoryForCoursesExport implements AsyncJobRepository
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

it('crea un trabajo asincrono pendiente y despacha el job de exportacion de cursos', function (): void {
    Queue::fake();
    $jobs = new InMemoryAsyncJobRepositoryForCoursesExport;
    $handler = new ExportCoursesHandler($jobs);

    $response = $handler->handle(new ExportCoursesCommand(requestedByUserId: 'user-1'));

    expect($response)->toBeInstanceOf(AsyncJobResponse::class)
        ->and($response->type)->toBe('export.courses')
        ->and($response->status)->toBe('pending')
        ->and($jobs->items)->toHaveCount(1);

    Queue::assertPushed(ExportCoursesJob::class, fn (ExportCoursesJob $job): bool => $job->asyncJobId === $response->id);
});
