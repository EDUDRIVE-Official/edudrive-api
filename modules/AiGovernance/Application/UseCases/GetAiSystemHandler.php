<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use Modules\AiGovernance\Application\Exceptions\AiSystemNotFound;
use Modules\AiGovernance\Application\Queries\GetAiSystemQuery;
use Modules\AiGovernance\Application\Responses\AiSystemResponse;
use Modules\AiGovernance\Domain\Repositories\AiSystemRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiSystemId;

final readonly class GetAiSystemHandler
{
    public function __construct(private AiSystemRepository $systems) {}

    public function handle(GetAiSystemQuery $query): AiSystemResponse
    {
        $system = $this->systems->findById(AiSystemId::fromString($query->aiSystemId));
        if ($system === null) {
            throw AiSystemNotFound::withId($query->aiSystemId);
        }

        return AiSystemResponse::fromSystem($system);
    }
}
