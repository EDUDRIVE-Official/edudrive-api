<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Gamification\Application\Commands\CompleteChallengeParticipationCommand;
use Modules\Gamification\Application\Commands\CreateChallengeCommand;
use Modules\Gamification\Application\Commands\JoinChallengeCommand;
use Modules\Gamification\Application\Commands\RetireChallengeCommand;
use Modules\Gamification\Application\Exceptions\ChallengeAlreadyExists;
use Modules\Gamification\Application\Exceptions\ChallengeAlreadyJoined;
use Modules\Gamification\Application\Exceptions\ChallengeNotAvailable;
use Modules\Gamification\Application\Exceptions\ChallengeNotFound;
use Modules\Gamification\Application\Exceptions\ChallengeParticipationNotFound;
use Modules\Gamification\Application\Queries\GetChallengeQuery;
use Modules\Gamification\Application\Queries\GetMyChallengeParticipationsQuery;
use Modules\Gamification\Application\Queries\ListChallengesQuery;
use Modules\Gamification\Application\Responses\ChallengeParticipationResponse;
use Modules\Gamification\Application\Responses\ChallengeResponse;
use Modules\Gamification\Application\UseCases\CompleteChallengeParticipationHandler;
use Modules\Gamification\Application\UseCases\CreateChallengeHandler;
use Modules\Gamification\Application\UseCases\GetChallengeHandler;
use Modules\Gamification\Application\UseCases\GetMyChallengeParticipationsHandler;
use Modules\Gamification\Application\UseCases\JoinChallengeHandler;
use Modules\Gamification\Application\UseCases\ListChallengesHandler;
use Modules\Gamification\Application\UseCases\RetireChallengeHandler;
use Modules\Gamification\Domain\Aggregates\Challenge;
use Modules\Gamification\Domain\Entities\ChallengeParticipation;
use Modules\Gamification\Domain\Enums\ChallengeType;
use Modules\Gamification\Domain\Exceptions\InvalidChallengeTransition;
use Modules\Gamification\Domain\Repositories\ChallengeParticipationRepository;
use Modules\Gamification\Domain\Repositories\ChallengeRepository;
use Modules\Gamification\Domain\ValueObjects\ChallengeCode;
use Modules\Gamification\Domain\ValueObjects\ChallengeId;

final class InMemoryChallengeRepository implements ChallengeRepository
{
    /** @var array<string, Challenge> */
    public array $items = [];

    public function save(Challenge $challenge): void
    {
        $this->items[$challenge->id()->value()] = $challenge;
    }

    public function findById(ChallengeId $id): ?Challenge
    {
        return $this->items[$id->value()] ?? null;
    }

    public function findByCode(ChallengeCode $code): ?Challenge
    {
        foreach ($this->items as $challenge) {
            if ($challenge->code()->equals($code)) {
                return $challenge;
            }
        }

        return null;
    }

    /** @return list<Challenge> */
    public function all(): array
    {
        return array_values($this->items);
    }
}

final class InMemoryChallengeParticipationRepository implements ChallengeParticipationRepository
{
    /** @var array<string, ChallengeParticipation> */
    public array $items = [];

    public function save(ChallengeParticipation $participation): void
    {
        $this->items[$participation->challengeId().'|'.$participation->userId()] = $participation;
    }

    public function findByChallengeAndUser(string $challengeId, string $userId): ?ChallengeParticipation
    {
        return $this->items[$challengeId.'|'.$userId] ?? null;
    }

    /** @return list<ChallengeParticipation> */
    public function allForUser(string $userId): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (ChallengeParticipation $item): bool => $item->userId() === $userId,
        ));
    }
}

function persistedChallengeFor(
    InMemoryChallengeRepository $repository,
    ?string $code = null,
    DateTimeImmutable $startsAt = new DateTimeImmutable('2026-09-01T00:00:00+00:00'),
    DateTimeImmutable $endsAt = new DateTimeImmutable('2026-09-08T00:00:00+00:00'),
): Challenge {
    $challenge = Challenge::create(
        id: ChallengeId::fromString((string) Str::uuid()),
        code: ChallengeCode::fromString($code ?? 'RETO-'.strtoupper((string) Str::random(6))),
        name: 'Semana de manejo seguro',
        description: 'Completa cinco sesiones prácticas sin infracciones durante la semana.',
        type: ChallengeType::Individual,
        reward: '100 puntos de experiencia.',
        startsAt: $startsAt,
        endsAt: $endsAt,
    );
    $repository->save($challenge);

    return $challenge;
}

it('crea un reto nuevo', function (): void {
    $challenges = new InMemoryChallengeRepository;

    $response = (new CreateChallengeHandler($challenges))->handle(new CreateChallengeCommand(
        code: 'semana-manejo-seguro',
        name: 'Semana de manejo seguro',
        description: 'Completa cinco sesiones prácticas sin infracciones durante la semana.',
        type: 'individual',
        reward: '100 puntos de experiencia.',
        startsAt: new DateTimeImmutable('2026-09-01T00:00:00+00:00'),
        endsAt: new DateTimeImmutable('2026-09-08T00:00:00+00:00'),
    ));

    expect($response)->toBeInstanceOf(ChallengeResponse::class)
        ->and($response->code)->toBe('SEMANA-MANEJO-SEGURO')
        ->and($response->status)->toBe('active');
});

it('rechaza crear un reto con un codigo duplicado', function (): void {
    $challenges = new InMemoryChallengeRepository;
    $challenge = persistedChallengeFor($challenges);

    expect(fn () => (new CreateChallengeHandler($challenges))->handle(new CreateChallengeCommand(
        code: $challenge->code()->value(),
        name: 'Otro nombre',
        description: 'Otra descripcion',
        type: 'group',
        reward: 'Otra recompensa',
        startsAt: new DateTimeImmutable('2026-10-01T00:00:00+00:00'),
        endsAt: new DateTimeImmutable('2026-10-08T00:00:00+00:00'),
    )))->toThrow(ChallengeAlreadyExists::class);
});

it('retira un reto existente', function (): void {
    $challenges = new InMemoryChallengeRepository;
    $challenge = persistedChallengeFor($challenges);

    $response = (new RetireChallengeHandler($challenges))->handle(new RetireChallengeCommand($challenge->id()->value(), 'Ya no aplica'));

    expect($response->status)->toBe('retired');
});

it('rechaza retirar un reto inexistente', function (): void {
    $challenges = new InMemoryChallengeRepository;

    expect(fn () => (new RetireChallengeHandler($challenges))->handle(new RetireChallengeCommand((string) Str::uuid())))
        ->toThrow(ChallengeNotFound::class);
});

it('propaga el rechazo de dominio al retirar dos veces', function (): void {
    $challenges = new InMemoryChallengeRepository;
    $challenge = persistedChallengeFor($challenges);
    (new RetireChallengeHandler($challenges))->handle(new RetireChallengeCommand($challenge->id()->value()));

    expect(fn () => (new RetireChallengeHandler($challenges))->handle(new RetireChallengeCommand($challenge->id()->value())))
        ->toThrow(InvalidChallengeTransition::class);
});

it('registra la union de un usuario a un reto activo dentro de su ventana', function (): void {
    $challenges = new InMemoryChallengeRepository;
    $participations = new InMemoryChallengeParticipationRepository;
    $challenge = persistedChallengeFor(
        $challenges,
        startsAt: new DateTimeImmutable('-1 day'),
        endsAt: new DateTimeImmutable('+1 day'),
    );
    $userId = (string) Str::uuid();

    $response = (new JoinChallengeHandler($challenges, $participations))->handle(new JoinChallengeCommand(
        challengeId: $challenge->id()->value(),
        userId: $userId,
    ));

    expect($response)->toBeInstanceOf(ChallengeParticipationResponse::class)
        ->and($response->challengeId)->toBe($challenge->id()->value())
        ->and($response->status)->toBe('joined');
});

it('rechaza unirse a un reto inexistente', function (): void {
    $challenges = new InMemoryChallengeRepository;
    $participations = new InMemoryChallengeParticipationRepository;

    expect(fn () => (new JoinChallengeHandler($challenges, $participations))->handle(new JoinChallengeCommand((string) Str::uuid(), (string) Str::uuid())))
        ->toThrow(ChallengeNotFound::class);
});

it('rechaza unirse a un reto retirado', function (): void {
    $challenges = new InMemoryChallengeRepository;
    $participations = new InMemoryChallengeParticipationRepository;
    $challenge = persistedChallengeFor($challenges, startsAt: new DateTimeImmutable('-1 day'), endsAt: new DateTimeImmutable('+1 day'));
    $challenge->retire(null, new DateTimeImmutable('now'));
    $challenges->save($challenge);

    expect(fn () => (new JoinChallengeHandler($challenges, $participations))->handle(new JoinChallengeCommand($challenge->id()->value(), (string) Str::uuid())))
        ->toThrow(ChallengeNotAvailable::class);
});

it('rechaza unirse a un reto fuera de su ventana de vigencia', function (): void {
    $challenges = new InMemoryChallengeRepository;
    $participations = new InMemoryChallengeParticipationRepository;
    $challenge = persistedChallengeFor($challenges, startsAt: new DateTimeImmutable('+1 day'), endsAt: new DateTimeImmutable('+2 days'));

    expect(fn () => (new JoinChallengeHandler($challenges, $participations))->handle(new JoinChallengeCommand($challenge->id()->value(), (string) Str::uuid())))
        ->toThrow(ChallengeNotAvailable::class);
});

it('rechaza unirse dos veces al mismo reto', function (): void {
    $challenges = new InMemoryChallengeRepository;
    $participations = new InMemoryChallengeParticipationRepository;
    $challenge = persistedChallengeFor($challenges, startsAt: new DateTimeImmutable('-1 day'), endsAt: new DateTimeImmutable('+1 day'));
    $userId = (string) Str::uuid();
    (new JoinChallengeHandler($challenges, $participations))->handle(new JoinChallengeCommand($challenge->id()->value(), $userId));

    expect(fn () => (new JoinChallengeHandler($challenges, $participations))->handle(new JoinChallengeCommand($challenge->id()->value(), $userId)))
        ->toThrow(ChallengeAlreadyJoined::class);
});

it('completa la participacion de un usuario ya unido', function (): void {
    $challenges = new InMemoryChallengeRepository;
    $participations = new InMemoryChallengeParticipationRepository;
    $challenge = persistedChallengeFor($challenges, startsAt: new DateTimeImmutable('-1 day'), endsAt: new DateTimeImmutable('+1 day'));
    $userId = (string) Str::uuid();
    (new JoinChallengeHandler($challenges, $participations))->handle(new JoinChallengeCommand($challenge->id()->value(), $userId));

    $response = (new CompleteChallengeParticipationHandler($participations))->handle(new CompleteChallengeParticipationCommand(
        challengeId: $challenge->id()->value(),
        userId: $userId,
        evidence: 'Completó las cinco sesiones sin infracciones.',
    ));

    expect($response->status)->toBe('completed')
        ->and($response->evidence)->toBe('Completó las cinco sesiones sin infracciones.');
});

it('rechaza completar una participacion inexistente', function (): void {
    $participations = new InMemoryChallengeParticipationRepository;

    expect(fn () => (new CompleteChallengeParticipationHandler($participations))->handle(new CompleteChallengeParticipationCommand((string) Str::uuid(), (string) Str::uuid())))
        ->toThrow(ChallengeParticipationNotFound::class);
});

it('consulta un reto por id', function (): void {
    $challenges = new InMemoryChallengeRepository;
    $challenge = persistedChallengeFor($challenges);

    $response = (new GetChallengeHandler($challenges))->handle(new GetChallengeQuery($challenge->id()->value()));

    expect($response->id)->toBe($challenge->id()->value());
});

it('rechaza consultar un reto inexistente', function (): void {
    $challenges = new InMemoryChallengeRepository;

    expect(fn () => (new GetChallengeHandler($challenges))->handle(new GetChallengeQuery((string) Str::uuid())))
        ->toThrow(ChallengeNotFound::class);
});

it('lista todos los retos del catalogo', function (): void {
    $challenges = new InMemoryChallengeRepository;
    persistedChallengeFor($challenges);
    persistedChallengeFor($challenges);

    $responses = (new ListChallengesHandler($challenges))->handle(new ListChallengesQuery);

    expect($responses)->toHaveCount(2)
        ->and($responses[0])->toBeInstanceOf(ChallengeResponse::class);
});

it('lista las participaciones del usuario autenticado', function (): void {
    $challenges = new InMemoryChallengeRepository;
    $participations = new InMemoryChallengeParticipationRepository;
    $challenge = persistedChallengeFor($challenges, startsAt: new DateTimeImmutable('-1 day'), endsAt: new DateTimeImmutable('+1 day'));
    $userId = (string) Str::uuid();
    (new JoinChallengeHandler($challenges, $participations))->handle(new JoinChallengeCommand($challenge->id()->value(), $userId));
    (new JoinChallengeHandler($challenges, $participations))->handle(new JoinChallengeCommand(
        persistedChallengeFor($challenges, startsAt: new DateTimeImmutable('-1 day'), endsAt: new DateTimeImmutable('+1 day'))->id()->value(),
        (string) Str::uuid(),
    ));

    $responses = (new GetMyChallengeParticipationsHandler($participations))->handle(new GetMyChallengeParticipationsQuery($userId));

    expect($responses)->toHaveCount(1)
        ->and($responses[0])->toBeInstanceOf(ChallengeParticipationResponse::class)
        ->and($responses[0]->challengeId)->toBe($challenge->id()->value());
});
