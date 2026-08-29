<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use DateTimeImmutable;
use Modules\AiGovernance\Application\Commands\ApproveAiSystemByCommitteeCommand;
use Modules\AiGovernance\Application\Exceptions\AiSystemNotFound;
use Modules\AiGovernance\Application\Responses\AiSystemResponse;
use Modules\AiGovernance\Domain\Repositories\AiSystemRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiSystemId;

final readonly class ApproveAiSystemByCommitteeHandler
{
    public function __construct(private AiSystemRepository $systems) {}

    public function handle(ApproveAiSystemByCommitteeCommand $command): AiSystemResponse
    {
        $system = $this->systems->findById(AiSystemId::fromString($command->aiSystemId));
        if ($system === null) {
            throw AiSystemNotFound::withId($command->aiSystemId);
        }

        $system->approveByCommittee(new DateTimeImmutable('now'));
        $this->systems->save($system);

        return AiSystemResponse::fromSystem($system);
    }
}
