<?php

declare(strict_types=1);

namespace Modules\AsyncProcessing\Application\UseCases;

use Modules\AsyncProcessing\Application\Exceptions\AsyncJobNotFound;
use Modules\AsyncProcessing\Application\Queries\GetAsyncJobQuery;
use Modules\AsyncProcessing\Application\Responses\AsyncJobResponse;
use Modules\AsyncProcessing\Domain\Repositories\AsyncJobRepository;
use Modules\AsyncProcessing\Domain\ValueObjects\AsyncJobId;

final readonly class GetAsyncJobHandler
{
    public function __construct(private AsyncJobRepository $jobs) {}

    public function handle(GetAsyncJobQuery $query): AsyncJobResponse
    {
        $job = $this->jobs->findById(AsyncJobId::fromString($query->asyncJobId));

        if ($job === null || $job->requestedByUserId() !== $query->requestedByUserId) {
            throw AsyncJobNotFound::withId($query->asyncJobId);
        }

        return AsyncJobResponse::fromJob($job);
    }
}
