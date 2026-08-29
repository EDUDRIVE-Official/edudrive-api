<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Analytics\Application\Commands\RequestAnalyticsReportCommand;
use Modules\Analytics\Application\Exceptions\InvalidAnalyticsReportType;
use Modules\Analytics\Domain\Enums\AnalyticsReportType;
use Modules\Analytics\Infrastructure\Jobs\GenerateAnalyticsReportJob;
use Modules\AsyncProcessing\Application\Responses\AsyncJobResponse;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;

final readonly class RequestAnalyticsReportHandler
{
    public function __construct(
        private AsyncJobRepository $jobs,
    ) {}

    public function handle(RequestAnalyticsReportCommand $command): AsyncJobResponse
    {
        $type = AnalyticsReportType::tryFrom($command->type);
        if ($type === null) {
            throw InvalidAnalyticsReportType::withValue($command->type);
        }

        $job = AsyncJob::request(
            id: AsyncJobId::fromString((string) Str::uuid()),
            type: 'analytics.'.$type->value,
            requestedByUserId: $command->requestedByUserId,
        );
        $this->jobs->save($job);

        GenerateAnalyticsReportJob::dispatch($job->id()->value(), $type->value);

        return AsyncJobResponse::fromJob($job);
    }
}
