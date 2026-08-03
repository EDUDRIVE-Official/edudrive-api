<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class ChangeProgramAudienceCommand implements Command
{
    /**
     * @param  list<string>  $licenseStages
     * @param  list<string>  $contexts
     * @param  list<string>  $vehicleTypes
     */
    public function __construct(
        public string $programId,
        public ?int $minAge,
        public ?int $maxAge,
        public array $licenseStages,
        public array $contexts,
        public array $vehicleTypes,
        public bool $minAgeProvided = true,
        public bool $maxAgeProvided = true,
        public bool $licenseStagesProvided = true,
        public bool $contextsProvided = true,
        public bool $vehicleTypesProvided = true,
    ) {}
}
