<?php

declare(strict_types=1);

use Modules\Academic\Domain\ValueObjects\GradingPolicy;

it('crea una politica de calificacion con sus flags', function (): void {
    $policy = new GradingPolicy(true, false);

    expect($policy->allowPartialCredit())->toBeTrue()
        ->and($policy->applyPenalties())->toBeFalse()
        ->and($policy->toArray())->toBe([
            'allow_partial_credit' => true,
            'apply_penalties' => false,
        ]);
});
