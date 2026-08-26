<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\UseCases;

use DateTimeImmutable;
use Modules\Simulation\Application\Commands\CancelSimulationSessionCommand;
use Modules\Simulation\Application\Exceptions\SimulationSessionNotFound;
use Modules\Simulation\Application\Responses\SimulationSessionResponse;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionId;

final readonly class CancelSimulationSessionHandler
{
    public function __construct(private SimulationSessionRepository $sessions) {}

    public function handle(CancelSimulationSessionCommand $command): SimulationSessionResponse
    {
        $session = $this->sessions->findById(SimulationSessionId::fromString($command->sessionId));
        if ($session === null) {
            throw SimulationSessionNotFound::withId($command->sessionId);
        }

        if ($session->userId() !== $command->userId && ! $command->canManageOthers) {
            throw SimulationSessionNotFound::withId($command->sessionId);
        }

        $session->cancel($command->reason, new DateTimeImmutable('now'));
        $this->sessions->save($session);

        return SimulationSessionResponse::fromSimulationSession($session);
    }
}
