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

        $currentAudience = $program->audience();

        $program->changeAudience(ProgramAudience::fromValues(
            minAge: $command->minAgeProvided ? $command->minAge : $currentAudience->minAge(),
            maxAge: $command->maxAgeProvided ? $command->maxAge : $currentAudience->maxAge(),
            licenseStages: $command->licenseStagesProvided
                ? array_map(
                    static fn (string $stage): LicenseStage => LicenseStage::from($stage),
                    $command->licenseStages,
                )
                : $currentAudience->licenseStages(),
            contexts: $command->contextsProvided
                ? array_map(
                    static fn (string $context): ProgramContext => ProgramContext::from($context),
                    $command->contexts,
                )
                : $currentAudience->contexts(),
            vehicleTypes: $command->vehicleTypesProvided
                ? array_map(
                    static fn (string $vehicleType): VehicleType => VehicleType::from($vehicleType),
                    $command->vehicleTypes,
                )
                : $currentAudience->vehicleTypes(),
        ));

        $this->programs->save($program);

        return ProgramResponse::fromProgram($program);
    }
}
