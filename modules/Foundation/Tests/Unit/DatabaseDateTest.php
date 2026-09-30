<?php

declare(strict_types=1);

use Modules\Foundation\Infrastructure\Persistence\DatabaseDate;

it('normaliza fechas sin cambiar el instante ni modificar el objeto original', function (string $offset, string $zone): void {
    config(['app.timezone' => $zone]);
    $source = new DateTime('2026-09-01T00:30:00'.$offset);
    $original = $source->format(DATE_ATOM);
    $normalized = DatabaseDate::normalize($source);

    expect($normalized?->getTimestamp())->toBe($source->getTimestamp())
        ->and($normalized?->getTimezone()->getName())->toBe($zone)
        ->and($source->format(DATE_ATOM))->toBe($original)
        ->and(DatabaseDate::normalize($normalized)?->format(DATE_ATOM))->toBe($normalized?->format(DATE_ATOM));
})->with(['+00:00', '-06:00', '+02:00'])->with(['America/Costa_Rica', 'UTC']);

it('conserva fechas ausentes como null', function (): void {
    expect(DatabaseDate::normalize(null))->toBeNull();
});
