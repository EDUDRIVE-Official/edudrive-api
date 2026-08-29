<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\AsyncProcessing\Application\Exceptions\AsyncJobNotFound;
use Modules\AsyncProcessing\Application\Queries\GetAsyncJobQuery;
use Modules\AsyncProcessing\Application\Responses\AsyncJobResponse;
use Modules\AsyncProcessing\Application\UseCases\GetAsyncJobHandler;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;

final class InMemoryAsyncJobRepository implements AsyncJobRepository
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

it('consulta un trabajo asincrono propio', function (): void {
    $repository = new InMemoryAsyncJobRepository;
    $userId = (string) Str::uuid();
    $job = AsyncJob::request(AsyncJobId::fromString((string) Str::uuid()), 'export.enrollments', $userId);
    $repository->save($job);

    $response = (new GetAsyncJobHandler($repository))->handle(new GetAsyncJobQuery($job->id()->value(), $userId));

    expect($response)->toBeInstanceOf(AsyncJobResponse::class)
        ->and($response->status)->toBe('pending');
});

it('rechaza consultar un trabajo asincrono inexistente', function (): void {
    $repository = new InMemoryAsyncJobRepository;

    expect(fn () => (new GetAsyncJobHandler($repository))->handle(new GetAsyncJobQuery((string) Str::uuid(), (string) Str::uuid())))
        ->toThrow(AsyncJobNotFound::class);
});

it('rechaza consultar un trabajo asincrono de otro usuario', function (): void {
    $repository = new InMemoryAsyncJobRepository;
    $job = AsyncJob::request(AsyncJobId::fromString((string) Str::uuid()), 'export.enrollments', (string) Str::uuid());
    $repository->save($job);

    expect(fn () => (new GetAsyncJobHandler($repository))->handle(new GetAsyncJobQuery($job->id()->value(), (string) Str::uuid())))
        ->toThrow(AsyncJobNotFound::class);
});
