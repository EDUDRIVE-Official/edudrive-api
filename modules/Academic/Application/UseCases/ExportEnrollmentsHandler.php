<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\ExportEnrollmentsCommand;
use Modules\Academic\Infrastructure\Jobs\ExportEnrollmentsJob;
use Modules\AsyncProcessing\Application\Responses\AsyncJobResponse;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;

final readonly class ExportEnrollmentsHandler
{
    public function __construct(
        private AsyncJobRepository $jobs,
    ) {}

    public function handle(ExportEnrollmentsCommand $command): AsyncJobResponse
    {
        $job = AsyncJob::request(
            id: AsyncJobId::fromString((string) Str::uuid()),
            type: 'export.enrollments',
            requestedByUserId: $command->requestedByUserId,
        );
        $this->jobs->save($job);

        ExportEnrollmentsJob::dispatch($job->id()->value());

        return AsyncJobResponse::fromJob($job);
    }
}
