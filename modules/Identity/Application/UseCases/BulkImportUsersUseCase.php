<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use Illuminate\Support\Str;
use Modules\AsyncProcessing\Application\Responses\AsyncJobResponse;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;
use Modules\Identity\Application\Commands\BulkImportUsersCommand;
use Modules\Identity\Infrastructure\Jobs\ImportUsersJob;

final readonly class BulkImportUsersUseCase
{
    public function __construct(
        private AsyncJobRepository $jobs,
    ) {}

    public function execute(BulkImportUsersCommand $command): AsyncJobResponse
    {
        $job = AsyncJob::request(
            id: AsyncJobId::fromString((string) Str::uuid()),
            type: 'import.users',
            requestedByUserId: $command->actorId,
        );
        $this->jobs->save($job);

        ImportUsersJob::dispatch($job->id()->value(), $command->rows, $command->actorId);

        return AsyncJobResponse::fromJob($job);
    }
}
