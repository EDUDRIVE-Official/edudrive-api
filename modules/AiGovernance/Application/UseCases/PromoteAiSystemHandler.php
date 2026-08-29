<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use DateTimeImmutable;
use Modules\AiGovernance\Application\Commands\PromoteAiSystemCommand;
use Modules\AiGovernance\Application\Exceptions\AiSystemNotFound;
use Modules\AiGovernance\Application\Exceptions\InvalidAiSystemStatus;
use Modules\AiGovernance\Application\Responses\AiSystemResponse;
use Modules\AiGovernance\Domain\Enums\AiSystemStatus;
use Modules\AiGovernance\Domain\Repositories\AiSystemRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiSystemId;

final readonly class PromoteAiSystemHandler
{
    public function __construct(private AiSystemRepository $systems) {}

    public function handle(PromoteAiSystemCommand $command): AiSystemResponse
    {
        $status = AiSystemStatus::tryFrom($command->status);
        if ($status === null) {
            throw InvalidAiSystemStatus::withValue($command->status);
        }

        $system = $this->systems->findById(AiSystemId::fromString($command->aiSystemId));
        if ($system === null) {
            throw AiSystemNotFound::withId($command->aiSystemId);
        }

        $system->promoteTo($status, new DateTimeImmutable('now'));
        $this->systems->save($system);

        return AiSystemResponse::fromSystem($system);
    }
}
