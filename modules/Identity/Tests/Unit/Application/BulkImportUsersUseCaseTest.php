<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Modules\AsyncProcessing\Application\Responses\AsyncJobResponse;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;
use Modules\Identity\Application\Commands\BulkImportUsersCommand;
use Modules\Identity\Application\UseCases\BulkImportUsersUseCase;
use Modules\Identity\Infrastructure\Jobs\ImportUsersJob;

final class InMemoryAsyncJobRepositoryForUsersImport implements AsyncJobRepository
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

it('crea un trabajo asincrono pendiente y despacha el job de importacion de usuarios', function (): void {
    Queue::fake();
    $jobs = new InMemoryAsyncJobRepositoryForUsersImport;
    $useCase = new BulkImportUsersUseCase($jobs);

    $response = $useCase->execute(new BulkImportUsersCommand(
        rows: [['name' => 'Ana', 'email' => 'ana@edudrive.cr', 'password' => 'secret123', 'role' => '']],
        actorId: 'actor-1',
    ));

    expect($response)->toBeInstanceOf(AsyncJobResponse::class)
        ->and($response->type)->toBe('import.users')
        ->and($response->status)->toBe('pending')
        ->and($jobs->items)->toHaveCount(1);

    Queue::assertPushed(ImportUsersJob::class, fn (ImportUsersJob $job): bool => $job->asyncJobId === $response->id && $job->actorId === 'actor-1');
});
