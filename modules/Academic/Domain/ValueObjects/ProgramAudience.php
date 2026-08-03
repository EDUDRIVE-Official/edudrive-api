<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\ValueObjects;

use InvalidArgumentException;
use Modules\Academic\Domain\Enums\LicenseStage;
use Modules\Academic\Domain\Enums\ProgramContext;
use Modules\Academic\Domain\Enums\VehicleType;

final readonly class ProgramAudience
{
    /**
     * @param  list<LicenseStage>  $licenseStages
     * @param  list<ProgramContext>  $contexts
     * @param  list<VehicleType>  $vehicleTypes
     */
    private function __construct(
        private ?int $minimumAge,
        private ?int $maximumAge,
        private array $licenseStages,
        private array $contexts,
        private array $vehicleTypes,
    ) {}

    /**
     * @param  list<LicenseStage>  $licenseStages
     * @param  list<ProgramContext>  $contexts
     * @param  list<VehicleType>  $vehicleTypes
     */
    public static function fromValues(
        ?int $minimumAge,
        ?int $maximumAge,
        array $licenseStages,
        array $contexts,
        array $vehicleTypes,
    ): self {
        if ($minimumAge !== null && $minimumAge < 0) {
            throw new InvalidArgumentException('La edad minima no puede ser negativa.');
        }

        if ($maximumAge !== null && $maximumAge < 0) {
            throw new InvalidArgumentException('La edad maxima no puede ser negativa.');
        }

        if ($minimumAge !== null && $maximumAge !== null && $minimumAge > $maximumAge) {
            throw new InvalidArgumentException('La edad minima no puede superar la edad maxima.');
        }

        return new self(
            minimumAge: $minimumAge,
            maximumAge: $maximumAge,
            licenseStages: self::uniqueLicenseStages($licenseStages),
            contexts: self::uniqueContexts($contexts),
            vehicleTypes: self::uniqueVehicleTypes($vehicleTypes),
        );
    }

    public function minimumAge(): ?int
    {
        return $this->minimumAge;
    }

    public function maximumAge(): ?int
    {
        return $this->maximumAge;
    }

    /** @return list<LicenseStage> */
    public function licenseStages(): array
    {
        return $this->licenseStages;
    }

    /** @return list<ProgramContext> */
    public function contexts(): array
    {
        return $this->contexts;
    }

    /** @return list<VehicleType> */
    public function vehicleTypes(): array
    {
        return $this->vehicleTypes;
    }

    /**
     * @param  list<LicenseStage>  $stages
     * @return list<LicenseStage>
     */
    private static function uniqueLicenseStages(array $stages): array
    {
        $unique = [];

        foreach ($stages as $stage) {
            $unique[$stage->value] ??= $stage;
        }

        return array_values($unique);
    }

    /**
     * @param  list<ProgramContext>  $contexts
     * @return list<ProgramContext>
     */
    private static function uniqueContexts(array $contexts): array
    {
        $unique = [];

        foreach ($contexts as $context) {
            $unique[$context->value] ??= $context;
        }

        return array_values($unique);
    }

    /**
     * @param  list<VehicleType>  $vehicleTypes
     * @return list<VehicleType>
     */
    private static function uniqueVehicleTypes(array $vehicleTypes): array
    {
        $unique = [];

        foreach ($vehicleTypes as $vehicleType) {
            $unique[$vehicleType->value] ??= $vehicleType;
        }

        return array_values($unique);
    }
}
