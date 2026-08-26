<?php

declare(strict_types=1);

use Modules\Academic\Domain\Entities\CompetencyGrade;
use Modules\Academic\Domain\ValueObjects\CompetencyId;

it('construye un breakdown de calificacion por competencia', function (): void {
    $grade = new CompetencyGrade(
        CompetencyId::fromString('01981a64-8300-7b1d-b442-764ea7f92103'),
        8,
        10,
        80,
    );

    expect($grade->score())->toBe(8)
        ->and($grade->totalPoints())->toBe(10)
        ->and($grade->percentage())->toBe(80)
        ->and($grade->toArray())->toBe([
            'competency_id' => '01981a64-8300-7b1d-b442-764ea7f92103',
            'score' => 8,
            'total_points' => 10,
            'percentage' => 80,
        ]);
});

it('rechaza una calificacion por competencia inconsistente', function (): void {
    expect(fn (): CompetencyGrade => new CompetencyGrade(
        CompetencyId::fromString('01981a64-8300-7b1d-b442-764ea7f92103'),
        -1,
        10,
        0,
    ))->toThrow(InvalidArgumentException::class);

    expect(fn (): CompetencyGrade => new CompetencyGrade(
        CompetencyId::fromString('01981a64-8300-7b1d-b442-764ea7f92103'),
        8,
        10,
        120,
    ))->toThrow(InvalidArgumentException::class);

    expect(fn (): CompetencyGrade => new CompetencyGrade(
        CompetencyId::fromString('01981a64-8300-7b1d-b442-764ea7f92103'),
        11,
        10,
        100,
    ))->toThrow(InvalidArgumentException::class);

    expect(fn (): CompetencyGrade => new CompetencyGrade(
        CompetencyId::fromString('01981a64-8300-7b1d-b442-764ea7f92103'),
        8,
        10,
        81,
    ))->toThrow(InvalidArgumentException::class);
});
