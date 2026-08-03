<?php

declare(strict_types=1);

namespace Modules\Academic\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Academic\Application\Commands\CreateProgramCommand;
use Modules\Academic\Application\Exceptions\ProgramCodeAlreadyExists;
use Modules\Academic\Application\Responses\ProgramResponse;
use Modules\Academic\Domain\Aggregates\EducationalProgram;
use Modules\Academic\Domain\Enums\LicenseStage;
use Modules\Academic\Domain\Enums\ProgramContext;
use Modules\Academic\Domain\Enums\VehicleType;
use Modules\Academic\Domain\Repositories\ProgramRepository;
use Modules\Academic\Domain\ValueObjects\ProgramAudience;
use Modules\Academic\Domain\ValueObjects\ProgramCode;
use Modules\Academic\Domain\ValueObjects\ProgramId;

final readonly class CreateProgramHandler
{
    public function __construct(private ProgramRepository $programs) {}

    public function handle(CreateProgramCommand $command): ProgramResponse
    {
        $code = ProgramCode::fromString($command->code);

        if ($this->programs->existsByCode($code)) {
            throw ProgramCodeAlreadyExists::forCode($code);
        }

        $program = EducationalProgram::create(
            id: ProgramId::fromString((string) Str::uuid()),
            code: $code,
            title: $command->title,
            description: $command->description,
            audience: ProgramAudience::fromValues(
                minAge: $command->minAge,
                maxAge: $command->maxAge,
                licenseStages: array_map(static fn (string $stage): LicenseStage => LicenseStage::from($stage), $command->licenseStages),
                contexts: array_map(static fn (string $context): ProgramContext => ProgramContext::from($context), $command->contexts),
                vehicleTypes: array_map(static fn (string $vehicleType): VehicleType => VehicleType::from($vehicleType), $command->vehicleTypes),
            ),
        );

        $this->programs->save($program);

        return ProgramResponse::fromProgram($program);
    }
}
