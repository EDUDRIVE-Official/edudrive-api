<?php

declare(strict_types=1);

namespace Modules\Simulation\Application\Services;

use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;

final readonly class ReportUserIdsResolver
{
    public function __construct(private SimulationSessionRepository $sessions) {}

    /**
     * @param  list<string>  $userIds
     * @return list<string>
     */
    public function resolve(array $userIds): array
    {
        if ($userIds !== []) {
            return array_values(array_unique($userIds));
        }

        $discovered = [];
        foreach ($this->sessions->all() as $session) {
            $discovered[$session->userId()] = true;
        }

        return array_keys($discovered);
    }
}
