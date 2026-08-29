<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\BulkImportCoursesCommand;
use Modules\Academic\Infrastructure\Jobs\ImportCoursesJob;
use Modules\AsyncProcessing\Application\Responses\AsyncJobResponse;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;

final readonly class BulkImportCoursesHandler
{
    public function __construct(
        private AsyncJobRepository $jobs,
    ) {}

    public function handle(BulkImportCoursesCommand $command): AsyncJobResponse
    {
        $job = AsyncJob::request(
            id: AsyncJobId::fromString((string) Str::uuid()),
            type: 'import.courses',
            requestedByUserId: $command->requestedByUserId,
        );
        $this->jobs->save($job);

        ImportCoursesJob::dispatch($job->id()->value(), $command->rows);

        return AsyncJobResponse::fromJob($job);
    }
}
