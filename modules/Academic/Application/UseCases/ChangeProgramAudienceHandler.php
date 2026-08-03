<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Modules\Academic\Application\Commands\ChangeProgramAudienceCommand;
use Modules\Academic\Application\Exceptions\ProgramNotFound;
use Modules\Academic\Application\Responses\ProgramResponse;
use Modules\Academic\Domain\Enums\LicenseStage;
use Modules\Academic\Domain\Enums\ProgramContext;
use Modules\Academic\Domain\Enums\VehicleType;
use Modules\Academic\Domain\Repositories\ProgramRepository;
use Modules\Academic\Domain\ValueObjects\ProgramAudience;
use Modules\Academic\Domain\ValueObjects\ProgramId;

final readonly class ChangeProgramAudienceHandler
{
    public function __construct(private ProgramRepository $programs) {}

    public function handle(ChangeProgramAudienceCommand $command): ProgramResponse
    {
        $programId = ProgramId::fromString($command->programId);
        $program = $this->programs->findById($programId);

        if ($program === null) {
            throw ProgramNotFound::withId($command->programId);
        }

        $program->changeAudience(ProgramAudience::fromValues(
            minAge: $command->minAge,
            maxAge: $command->maxAge,
            licenseStages: array_map(
                static fn (string $stage): LicenseStage => LicenseStage::from($stage),
                $command->licenseStages,
            ),
            contexts: array_map(
                static fn (string $context): ProgramContext => ProgramContext::from($context),
                $command->contexts,
            ),
            vehicleTypes: array_map(
                static fn (string $vehicleType): VehicleType => VehicleType::from($vehicleType),
                $command->vehicleTypes,
            ),
        ));

        $this->programs->save($program);

        return ProgramResponse::fromProgram($program);
    }
}
