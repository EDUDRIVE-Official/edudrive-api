<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class CreateProgramCommand implements Command
{
    /**
     * @param  list<string>  $licenseStages
     * @param  list<string>  $contexts
     * @param  list<string>  $vehicleTypes
     */
    public function __construct(
        public string $code,
        public string $title,
        public string $description,
        public ?int $minAge,
        public ?int $maxAge,
        public array $licenseStages,
        public array $contexts,
        public array $vehicleTypes,
    ) {}
}
