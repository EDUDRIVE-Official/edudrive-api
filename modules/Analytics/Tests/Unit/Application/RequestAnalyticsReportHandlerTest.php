<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Queue;
use Modules\Analytics\Application\Commands\RequestAnalyticsReportCommand;
use Modules\Analytics\Application\Exceptions\InvalidAnalyticsReportType;
use Modules\Analytics\Application\UseCases\RequestAnalyticsReportHandler;
use Modules\Analytics\Infrastructure\Jobs\GenerateAnalyticsReportJob;
use Modules\AsyncProcessing\Application\Responses\AsyncJobResponse;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;

final class InMemoryAsyncJobRepositoryForAnalytics implements AsyncJobRepository
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

it('crea un trabajo asincrono pendiente y despacha el job de generacion del reporte', function (): void {
    Queue::fake();
    $jobs = new InMemoryAsyncJobRepositoryForAnalytics;
    $handler = new RequestAnalyticsReportHandler($jobs);

    $response = $handler->handle(new RequestAnalyticsReportCommand(type: 'enrollments_summary', requestedByUserId: 'user-1'));

    expect($response)->toBeInstanceOf(AsyncJobResponse::class)
        ->and($response->type)->toBe('analytics.enrollments_summary')
        ->and($response->status)->toBe('pending')
        ->and($jobs->items)->toHaveCount(1);

    Queue::assertPushed(GenerateAnalyticsReportJob::class, fn (GenerateAnalyticsReportJob $job): bool => $job->asyncJobId === $response->id && $job->reportType === 'enrollments_summary');
});

it('rechaza un tipo de reporte invalido', function (): void {
    $jobs = new InMemoryAsyncJobRepositoryForAnalytics;
    $handler = new RequestAnalyticsReportHandler($jobs);

    expect(fn () => $handler->handle(new RequestAnalyticsReportCommand(type: 'no_existe', requestedByUserId: 'user-1')))
        ->toThrow(InvalidAnalyticsReportType::class);
});
