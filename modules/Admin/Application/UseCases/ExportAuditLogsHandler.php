<?php

declare(strict_types=1);

namespace Modules\Admin\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Admin\Application\Commands\ExportAuditLogsCommand;
use Modules\Admin\Infrastructure\Jobs\ExportAuditLogsJob;
use Modules\AsyncProcessing\Application\Responses\AsyncJobResponse;
use Modules\AsyncProcessing\Domain\Aggregates\AsyncJob;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;

final readonly class ExportAuditLogsHandler
{
    public function __construct(
        private AsyncJobRepository $jobs,
    ) {}

    public function handle(ExportAuditLogsCommand $command): AsyncJobResponse
    {
        $job = AsyncJob::request(
            id: AsyncJobId::fromString((string) Str::uuid()),
            type: 'export.audit_logs',
            requestedByUserId: $command->requestedByUserId,
        );
        $this->jobs->save($job);

        ExportAuditLogsJob::dispatch($job->id()->value());

        return AsyncJobResponse::fromJob($job);
    }
}
