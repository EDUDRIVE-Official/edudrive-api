<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Simulation\Domain\Aggregates\SimulationSession;
use Modules\Simulation\Domain\Aggregates\Simulator;
use Modules\Simulation\Domain\Repositories\SimulationSessionRepository;
use Modules\Simulation\Domain\Repositories\SimulatorRepository;
use Modules\Simulation\Domain\ValueObjects\DeviceIdentifier;
use Modules\Simulation\Domain\ValueObjects\IntegrationKey;
use Modules\Simulation\Domain\ValueObjects\SimulationSessionId;
use Modules\Simulation\Domain\ValueObjects\SimulatorId;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedSimulationWebUser(string $name = 'Estudiante SIMUDRIVE'): User
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: $name,
        email: Email::fromString(Str::uuid().'@edudrive.cr'),
        passwordHash: 'hash',
    );
    app(UserRepository::class)->save($user);

    return $user;
}

function persistedSimulationWebSession(string $userId): SimulationSession
{
    $simulator = Simulator::register(
        id: SimulatorId::fromString((string) Str::uuid()),
        deviceIdentifier: DeviceIdentifier::fromString('WEB-'.strtoupper(Str::random(8))),
        softwareVersion: '1.0.0',
        location: 'San José',
        integrationKey: IntegrationKey::generate(),
    );
    app(SimulatorRepository::class)->save($simulator);

    $session = SimulationSession::schedule(
        id: SimulationSessionId::fromString((string) Str::uuid()),
        userId: $userId,
        simulatorId: $simulator->id()->value(),
        vehicleType: 'sedan',
        scenario: 'circuito-urbano',
        scheduledAt: new DateTimeImmutable('2026-09-07 10:00:00'),
        plannedDurationMinutes: 45,
    );
    app(SimulationSessionRepository::class)->save($session);

    return $session;
}

it('requiere autenticación para consultar las simulaciones propias', function (): void {
    /** @var TestCase $this */
    $this->get('/mis-simulaciones')->assertRedirect(route('login'));
});

it('muestra únicamente las sesiones del usuario autenticado', function (): void {
    /** @var TestCase $this */
    $user = persistedSimulationWebUser();
    persistedSimulationWebSession($user->id());
    $other = persistedSimulationWebUser('Otro estudiante');
    persistedSimulationWebSession($other->id());
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->get('/mis-simulaciones')
        ->assertOk()
        ->assertSeeText('Circuito Urbano')
        ->assertSeeText('1')
        ->assertDontSeeText('Otro estudiante');
});

it('protege y muestra los reportes consolidados de simulación', function (): void {
    /** @var TestCase $this */
    $user = persistedSimulationWebUser();
    persistedSimulationWebSession($user->id());
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');
    $this->get('/admin/reportes-simulacion')->assertForbidden();

    $this->actingAs(actingAsSuperAdminUser(), 'web');
    $this->get('/admin/reportes-simulacion')
        ->assertOk()
        ->assertSeeText('Reportes de simulación')
        ->assertSeeText($user->name())
        ->assertDontSeeText($user->id());
});
