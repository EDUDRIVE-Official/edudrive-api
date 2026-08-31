<?php

declare(strict_types=1);

use Modules\Academic\Domain\Enums\CourseStatus;

it('devuelve la etiqueta legible de cada estado', function (): void {
    expect(CourseStatus::Draft->label())->toBe('Borrador')
        ->and(CourseStatus::UnderReview->label())->toBe('En revisión')
        ->and(CourseStatus::Approved->label())->toBe('Aprobado')
        ->and(CourseStatus::Published->label())->toBe('Publicado')
        ->and(CourseStatus::Archived->label())->toBe('Archivado');
});
