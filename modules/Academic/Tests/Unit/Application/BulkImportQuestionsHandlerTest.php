<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Modules\Academic\Application\Commands\BulkImportQuestionsCommand;
use Modules\Academic\Application\UseCases\BulkImportQuestionsHandler;
use Modules\Academic\Infrastructure\Jobs\ImportQuestionsJob;
use Modules\AsyncProcessing\Application\Responses\AsyncJobResponse;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;

final class InMemoryAsyncJobRepositoryForQuestionsImport implements AsyncJobRepository
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

it('crea un trabajo asincrono pendiente y despacha el job de importacion de preguntas', function (): void {
    Queue::fake();
    $jobs = new InMemoryAsyncJobRepositoryForQuestionsImport;
    $handler = new BulkImportQuestionsHandler($jobs);

    $response = $handler->handle(new BulkImportQuestionsCommand(rows: [
        ['competency_code' => 'X', 'type' => 'single_choice', 'prompt' => 'p', 'score' => '1', 'response' => '{}', 'options' => '[]', 'explanation' => '', 'media' => '', 'source_kind' => '', 'source_reference' => '', 'license_categories' => ''],
    ], requestedByUserId: 'user-1'));

    expect($response)->toBeInstanceOf(AsyncJobResponse::class)
        ->and($response->type)->toBe('import.questions')
        ->and($response->status)->toBe('pending')
        ->and($jobs->items)->toHaveCount(1);

    Queue::assertPushed(ImportQuestionsJob::class, fn (ImportQuestionsJob $job): bool => $job->asyncJobId === $response->id && count($job->rows) === 1);
});
