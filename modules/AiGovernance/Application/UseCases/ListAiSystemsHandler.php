<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use Modules\AiGovernance\Application\Queries\ListAiSystemsQuery;
use Modules\AiGovernance\Application\Responses\AiSystemResponse;
use Modules\AiGovernance\Domain\Aggregates\AiSystem;
use Modules\AiGovernance\Domain\Repositories\AiSystemRepository;

final readonly class ListAiSystemsHandler
{
    public function __construct(private AiSystemRepository $systems) {}

    /** @return list<AiSystemResponse> */
    public function handle(ListAiSystemsQuery $query): array
    {
        return array_map(
            static fn (AiSystem $system): AiSystemResponse => AiSystemResponse::fromSystem($system),
            $this->systems->all(),
        );
    }
}
