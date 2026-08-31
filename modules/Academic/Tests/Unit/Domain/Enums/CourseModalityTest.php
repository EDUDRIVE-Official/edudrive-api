<?php

declare(strict_types=1);

use Modules\Academic\Domain\Enums\CourseModality;

it('devuelve la etiqueta legible de cada modalidad', function (): void {
    expect(CourseModality::InPerson->label())->toBe('Presencial')
        ->and(CourseModality::Virtual->label())->toBe('Virtual')
        ->and(CourseModality::Hybrid->label())->toBe('Híbrida');
});
