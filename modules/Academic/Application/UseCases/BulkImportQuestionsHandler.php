<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\BulkImportQuestionsCommand;
use Modules\Academic\Infrastructure\Jobs\ImportQuestionsJob;
use Modules\AsyncProcessing\Application\Responses\AsyncJobResponse;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;

final readonly class BulkImportQuestionsHandler
{
    public function __construct(
        private AsyncJobRepository $jobs,
    ) {}

    public function handle(BulkImportQuestionsCommand $command): AsyncJobResponse
    {
        $job = AsyncJob::request(
            id: AsyncJobId::fromString((string) Str::uuid()),
            type: 'import.questions',
            requestedByUserId: $command->requestedByUserId,
        );
        $this->jobs->save($job);

        ImportQuestionsJob::dispatch($job->id()->value(), $command->rows);

        return AsyncJobResponse::fromJob($job);
    }
}
