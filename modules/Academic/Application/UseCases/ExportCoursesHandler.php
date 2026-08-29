<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\ExportCoursesCommand;
use Modules\Academic\Infrastructure\Jobs\ExportCoursesJob;
use Modules\AsyncProcessing\Application\Responses\AsyncJobResponse;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;

final readonly class ExportCoursesHandler
{
    public function __construct(
        private AsyncJobRepository $jobs,
    ) {}

    public function handle(ExportCoursesCommand $command): AsyncJobResponse
    {
        $job = AsyncJob::request(
            id: AsyncJobId::fromString((string) Str::uuid()),
            type: 'export.courses',
            requestedByUserId: $command->requestedByUserId,
        );
        $this->jobs->save($job);

        ExportCoursesJob::dispatch($job->id()->value());

        return AsyncJobResponse::fromJob($job);
    }
}
