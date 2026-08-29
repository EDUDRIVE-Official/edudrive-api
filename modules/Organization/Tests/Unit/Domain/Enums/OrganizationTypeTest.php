<?php

declare(strict_types=1);

use Modules\Organization\Domain\Enums\OrganizationType;

it('expone una etiqueta legible para cada caso, incluida universidad', function (): void {
    expect(OrganizationType::University->label())->toBe('Universidad');

    foreach (OrganizationType::cases() as $type) {
        expect($type->label())->toBeString()->not->toBe('');
    }
});
