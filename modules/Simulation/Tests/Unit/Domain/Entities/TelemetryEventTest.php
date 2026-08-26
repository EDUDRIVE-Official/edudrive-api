<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Simulation\Domain\Entities\TelemetryEvent;
use Modules\Simulation\Domain\Enums\TelemetryEventType;

it('registra un evento de telemetria valido', function (): void {
    $event = TelemetryEvent::record(
        id: (string) Str::uuid(),
        sessionId: (string) Str::uuid(),
        type: TelemetryEventType::Collision,
        details: 'Colision leve con cono',
        occurredAt: new DateTimeImmutable('2026-09-01T10:12:00+00:00'),
    );

    expect($event->type())->toBe(TelemetryEventType::Collision)
        ->and($event->details())->toBe('Colision leve con cono');
});

it('acepta un evento sin detalle', function (): void {
    $event = TelemetryEvent::record(
        id: (string) Str::uuid(),
        sessionId: (string) Str::uuid(),
        type: TelemetryEventType::SignalUsage,
        details: null,
        occurredAt: new DateTimeImmutable('now'),
    );

    expect($event->details())->toBeNull();
});
