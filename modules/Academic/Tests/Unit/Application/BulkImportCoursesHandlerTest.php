<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Modules\Academic\Application\Commands\BulkImportCoursesCommand;
use Modules\Academic\Application\UseCases\BulkImportCoursesHandler;
use Modules\Academic\Infrastructure\Jobs\ImportCoursesJob;
use Modules\AsyncProcessing\Application\Responses\AsyncJobResponse;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;

final class InMemoryAsyncJobRepositoryForCoursesImport implements AsyncJobRepository
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
}

it('crea un trabajo asincrono pendiente y despacha el job de importacion de cursos', function (): void {
    Queue::fake();
    $jobs = new InMemoryAsyncJobRepositoryForCoursesImport;
    $handler = new BulkImportCoursesHandler($jobs);

    $response = $handler->handle(new BulkImportCoursesCommand(rows: [
        ['code' => 'IMP-1', 'title' => 'Curso', 'description' => '', 'objectives' => '', 'prerequisites' => '', 'modality' => '', 'duration_hours' => ''],
    ], requestedByUserId: 'user-1'));

    expect($response)->toBeInstanceOf(AsyncJobResponse::class)
        ->and($response->type)->toBe('import.courses')
        ->and($response->status)->toBe('pending')
        ->and($jobs->items)->toHaveCount(1);

    Queue::assertPushed(ImportCoursesJob::class, fn (ImportCoursesJob $job): bool => $job->asyncJobId === $response->id && count($job->rows) === 1);
});
