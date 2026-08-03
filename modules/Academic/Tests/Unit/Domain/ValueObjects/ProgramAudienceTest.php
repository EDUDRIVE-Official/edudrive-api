<?php

declare(strict_types=1);

use Modules\Academic\Domain\Enums\LicenseStage;
use Modules\Academic\Domain\Enums\ProgramContext;
use Modules\Academic\Domain\Enums\VehicleType;
use Modules\Academic\Domain\ValueObjects\ProgramAudience;

it('crea una audiencia con edades opcionales validas', function (?int $minAge, ?int $maxAge): void {
    $audience = ProgramAudience::fromValues(
        minAge: $minAge,
        maxAge: $maxAge,
        licenseStages: [],
        contexts: [],
        vehicleTypes: [],
    );

    expect($audience->minAge())->toBe($minAge)
        ->and($audience->maxAge())->toBe($maxAge);
})->with([
    'sin limites de edad' => [null, null],
    'solo edad minima' => [16, null],
    'solo edad maxima' => [null, 80],
    'rango completo' => [16, 18],
]);

it('rechaza una edad minima negativa', function (): void {
    ProgramAudience::fromValues(-1, null, [], [], []);
})->throws(InvalidArgumentException::class, 'La edad minima no puede ser negativa.');

it('rechaza una edad maxima negativa', function (): void {
    ProgramAudience::fromValues(null, -1, [], [], []);
})->throws(InvalidArgumentException::class, 'La edad maxima no puede ser negativa.');

it('rechaza un rango cuya edad minima supera la maxima', function (): void {
    ProgramAudience::fromValues(19, 18, [], [], []);
})->throws(InvalidArgumentException::class, 'La edad minima no puede superar la edad maxima.');

it('deduplica las restricciones por valor y conserva su primer orden', function (): void {
    $audience = ProgramAudience::fromValues(
        minAge: null,
        maxAge: null,
        licenseStages: [LicenseStage::Learner, LicenseStage::Unlicensed, LicenseStage::Learner],
        contexts: [ProgramContext::Corporate, ProgramContext::General, ProgramContext::Corporate],
        vehicleTypes: [VehicleType::Automobile, VehicleType::Motorcycle, VehicleType::Automobile],
    );

    expect($audience->licenseStages())->toBe([
        LicenseStage::Learner,
        LicenseStage::Unlicensed,
    ])->and($audience->contexts())->toBe([
        ProgramContext::Corporate,
        ProgramContext::General,
    ])->and($audience->vehicleTypes())->toBe([
        VehicleType::Automobile,
        VehicleType::Motorcycle,
    ])->and(array_keys($audience->licenseStages()))->toBe([0, 1])
        ->and(array_keys($audience->contexts()))->toBe([0, 1])
        ->and(array_keys($audience->vehicleTypes()))->toBe([0, 1]);
});
