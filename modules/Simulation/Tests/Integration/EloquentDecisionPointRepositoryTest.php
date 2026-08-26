<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Simulation\Domain\Aggregates\SimulationSession;
use Modules\Simulation\Domain\Aggregates\Simulator;
use Modules\Simulation\Domain\Entities\DecisionPoint;
use Modules\Simulation\Domain\Enums\DecisionRiskLevel;
use Modules\Simulation\Domain\Enums\DriverReactionType;
use Modules\Simulation\Domain\Repositories\DecisionPointRepository;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\Repositories\SimulatorRepository;
use Modules\Simulation\Domain\ValueObjects\DeviceIdentifier;
use Modules\Simulation\Domain\ValueObjects\IntegrationKey;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionId;
use Modules\Simulation\Domain\ValueObjects\SimulatorId;

uses(RefreshDatabase::class);

function persistedDecisionPointSessionId(): string
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante de decisiones',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    $simulator = Simulator::register(
        id: SimulatorId::fromString((string) Str::uuid()),
        deviceIdentifier: DeviceIdentifier::fromString('SIM-'.strtoupper((string) Str::random(6))),
        softwareVersion: '1.0.0',
        location: null,
        integrationKey: IntegrationKey::generate(),
    );
    app(SimulatorRepository::class)->save($simulator);

    $session = SimulationSession::schedule(
        id: SimulationSessionId::fromString((string) Str::uuid()),
        userId: $user->id(),
        simulatorId: $simulator->id()->value(),
        vehicleType: 'sedan',
        scenario: 'circuito-urbano',
        scheduledAt: new DateTimeImmutable('2026-09-01T10:00:00+00:00'),
        plannedDurationMinutes: 45,
    );
    app(SimulationSessionRepository::class)->save($session);

    return $session->id()->value();
}

it('guarda y recupera puntos de decision en lote', function (): void {
    $sessionId = persistedDecisionPointSessionId();
    $points = [
        DecisionPoint::record((string) Str::uuid(), $sessionId, 'Semáforo en amarillo', DecisionRiskLevel::High, DriverReactionType::Braked, new DateTimeImmutable('2026-09-01T10:12:00+00:00')),
        DecisionPoint::record((string) Str::uuid(), $sessionId, 'Peatón cruzando', DecisionRiskLevel::Medium, DriverReactionType::Signaled, new DateTimeImmutable('2026-09-01T10:13:00+00:00')),
    ];

    app(DecisionPointRepository::class)->saveBatch($points);
    $found = app(DecisionPointRepository::class)->allForSession($sessionId);

    expect($found)->toHaveCount(2)
        ->and($found[0]->roadContext())->toBe('Semáforo en amarillo')
        ->and($found[0]->riskLevel())->toBe(DecisionRiskLevel::High)
        ->and($found[1]->driverReaction())->toBe(DriverReactionType::Signaled);
});

it('no falla al guardar un lote vacio de puntos de decision', function (): void {
    app(DecisionPointRepository::class)->saveBatch([]);

    expect(true)->toBeTrue();
});
