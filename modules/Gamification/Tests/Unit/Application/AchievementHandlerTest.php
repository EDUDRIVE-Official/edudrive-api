<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Gamification\Application\Commands\CreateAchievementCommand;
use Modules\Gamification\Application\Commands\GrantAchievementCommand;
use Modules\Gamification\Application\Commands\RetireAchievementCommand;
use Modules\Gamification\Application\Exceptions\AchievementAlreadyExists;
use Modules\Gamification\Application\Exceptions\AchievementAlreadyGranted;
use Modules\Gamification\Application\Exceptions\AchievementNotAvailable;
use Modules\Gamification\Application\Exceptions\AchievementNotFound;
use Modules\Gamification\Application\Queries\GetAchievementQuery;
use Modules\Gamification\Application\Queries\GetMyAchievementsQuery;
use Modules\Gamification\Application\Queries\ListAchievementsQuery;
use Modules\Gamification\Application\Responses\AchievementResponse;
use Modules\Gamification\Application\Responses\UserAchievementResponse;
use Modules\Gamification\Application\UseCases\CreateAchievementHandler;
use Modules\Gamification\Application\UseCases\GetAchievementHandler;
use Modules\Gamification\Application\UseCases\GetMyAchievementsHandler;
use Modules\Gamification\Application\UseCases\GrantAchievementHandler;
use Modules\Gamification\Application\UseCases\ListAchievementsHandler;
use Modules\Gamification\Application\UseCases\RetireAchievementHandler;
use Modules\Gamification\Domain\Aggregates\Achievement;
use Modules\Gamification\Domain\Entities\UserAchievement;
use Modules\Gamification\Domain\Exceptions\InvalidAchievementTransition;
use Modules\Gamification\Domain\Repositories\AchievementRepository;
use Modules\Gamification\Domain\Repositories\UserAchievementRepository;
use Modules\Gamification\Domain\ValueObjects\AchievementCode;
use Modules\Gamification\Domain\ValueObjects\AchievementId;

final class InMemoryAchievementRepository implements AchievementRepository
{
    /** @var array<string, Achievement> */
    public array $items = [];

    public function save(Achievement $achievement): void
    {
        $this->items[$achievement->id()->value()] = $achievement;
    }

    public function findById(AchievementId $id): ?Achievement
    {
        return $this->items[$id->value()] ?? null;
    }

    public function findByCode(AchievementCode $code): ?Achievement
    {
        foreach ($this->items as $achievement) {
            if ($achievement->code()->equals($code)) {
                return $achievement;
            }
        }

        return null;
    }

    /** @return list<Achievement> */
    public function all(): array
    {
        return array_values($this->items);
    }
}

final class InMemoryUserAchievementRepository implements UserAchievementRepository
{
    /** @var list<UserAchievement> */
    public array $items = [];

    public function save(UserAchievement $userAchievement): void
    {
        $this->items[] = $userAchievement;
    }

    public function findByAchievementAndUser(string $achievementId, string $userId): ?UserAchievement
    {
        foreach ($this->items as $item) {
            if ($item->achievementId() === $achievementId && $item->userId() === $userId) {
                return $item;
            }
        }

        return null;
    }

    /** @return list<UserAchievement> */
    public function allForUser(string $userId): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (UserAchievement $item): bool => $item->userId() === $userId,
        ));
    }
}

function persistedAchievementFor(InMemoryAchievementRepository $repository, ?string $code = null): Achievement
{
    $achievement = Achievement::create(
        id: AchievementId::fromString((string) Str::uuid()),
        code: AchievementCode::fromString($code ?? 'LOGRO-'.strtoupper((string) Str::random(6))),
        name: 'Primer curso completado',
        description: 'Se otorga al completar el primer curso.',
        earningRule: 'Completar cualquier curso por primera vez.',
    );
    $repository->save($achievement);

    return $achievement;
}

it('crea un logro nuevo', function (): void {
    $achievements = new InMemoryAchievementRepository;

    $response = (new CreateAchievementHandler($achievements))->handle(new CreateAchievementCommand(
        code: 'primer-curso-completado',
        name: 'Primer curso completado',
        description: 'Se otorga al completar el primer curso.',
        earningRule: 'Completar cualquier curso por primera vez.',
    ));

    expect($response)->toBeInstanceOf(AchievementResponse::class)
        ->and($response->code)->toBe('PRIMER-CURSO-COMPLETADO')
        ->and($response->status)->toBe('active');
});

it('rechaza crear un logro con un codigo duplicado', function (): void {
    $achievements = new InMemoryAchievementRepository;
    $achievement = persistedAchievementFor($achievements);

    expect(fn () => (new CreateAchievementHandler($achievements))->handle(new CreateAchievementCommand(
        code: $achievement->code()->value(),
        name: 'Otro nombre',
        description: 'Otra descripcion',
        earningRule: 'Otra regla',
    )))->toThrow(AchievementAlreadyExists::class);
});

it('retira un logro existente', function (): void {
    $achievements = new InMemoryAchievementRepository;
    $achievement = persistedAchievementFor($achievements);

    $response = (new RetireAchievementHandler($achievements))->handle(new RetireAchievementCommand($achievement->id()->value(), 'Ya no aplica'));

    expect($response->status)->toBe('retired');
});

it('rechaza retirar un logro inexistente', function (): void {
    $achievements = new InMemoryAchievementRepository;

    expect(fn () => (new RetireAchievementHandler($achievements))->handle(new RetireAchievementCommand((string) Str::uuid())))
        ->toThrow(AchievementNotFound::class);
});

it('propaga el rechazo de dominio al retirar dos veces', function (): void {
    $achievements = new InMemoryAchievementRepository;
    $achievement = persistedAchievementFor($achievements);
    (new RetireAchievementHandler($achievements))->handle(new RetireAchievementCommand($achievement->id()->value()));

    expect(fn () => (new RetireAchievementHandler($achievements))->handle(new RetireAchievementCommand($achievement->id()->value())))
        ->toThrow(InvalidAchievementTransition::class);
});

it('otorga un logro activo a un usuario', function (): void {
    $achievements = new InMemoryAchievementRepository;
    $userAchievements = new InMemoryUserAchievementRepository;
    $achievement = persistedAchievementFor($achievements);
    $userId = (string) Str::uuid();

    $response = (new GrantAchievementHandler($achievements, $userAchievements))->handle(new GrantAchievementCommand(
        achievementId: $achievement->id()->value(),
        userId: $userId,
        evidence: 'Completó el curso con 95% de aciertos.',
    ));

    expect($response)->toBeInstanceOf(UserAchievementResponse::class)
        ->and($response->achievementId)->toBe($achievement->id()->value())
        ->and($response->evidence)->toBe('Completó el curso con 95% de aciertos.');
});

it('rechaza otorgar un logro inexistente', function (): void {
    $achievements = new InMemoryAchievementRepository;
    $userAchievements = new InMemoryUserAchievementRepository;

    expect(fn () => (new GrantAchievementHandler($achievements, $userAchievements))->handle(new GrantAchievementCommand((string) Str::uuid(), (string) Str::uuid(), 'Evidencia')))
        ->toThrow(AchievementNotFound::class);
});

it('rechaza otorgar un logro retirado', function (): void {
    $achievements = new InMemoryAchievementRepository;
    $userAchievements = new InMemoryUserAchievementRepository;
    $achievement = persistedAchievementFor($achievements);
    $achievement->retire(null, new DateTimeImmutable('now'));
    $achievements->save($achievement);

    expect(fn () => (new GrantAchievementHandler($achievements, $userAchievements))->handle(new GrantAchievementCommand($achievement->id()->value(), (string) Str::uuid(), 'Evidencia')))
        ->toThrow(AchievementNotAvailable::class);
});

it('rechaza otorgar el mismo logro dos veces al mismo usuario', function (): void {
    $achievements = new InMemoryAchievementRepository;
    $userAchievements = new InMemoryUserAchievementRepository;
    $achievement = persistedAchievementFor($achievements);
    $userId = (string) Str::uuid();
    (new GrantAchievementHandler($achievements, $userAchievements))->handle(new GrantAchievementCommand($achievement->id()->value(), $userId, 'Primera vez'));

    expect(fn () => (new GrantAchievementHandler($achievements, $userAchievements))->handle(new GrantAchievementCommand($achievement->id()->value(), $userId, 'Segunda vez')))
        ->toThrow(AchievementAlreadyGranted::class);
});

it('consulta un logro por id', function (): void {
    $achievements = new InMemoryAchievementRepository;
    $achievement = persistedAchievementFor($achievements);

    $response = (new GetAchievementHandler($achievements))->handle(new GetAchievementQuery($achievement->id()->value()));

    expect($response->id)->toBe($achievement->id()->value());
});

it('rechaza consultar un logro inexistente', function (): void {
    $achievements = new InMemoryAchievementRepository;

    expect(fn () => (new GetAchievementHandler($achievements))->handle(new GetAchievementQuery((string) Str::uuid())))
        ->toThrow(AchievementNotFound::class);
});

it('lista todos los logros del catalogo', function (): void {
    $achievements = new InMemoryAchievementRepository;
    persistedAchievementFor($achievements);
    persistedAchievementFor($achievements);

    $responses = (new ListAchievementsHandler($achievements))->handle(new ListAchievementsQuery);

    expect($responses)->toHaveCount(2)
        ->and($responses[0])->toBeInstanceOf(AchievementResponse::class);
});

it('lista los logros obtenidos por el usuario autenticado', function (): void {
    $achievements = new InMemoryAchievementRepository;
    $userAchievements = new InMemoryUserAchievementRepository;
    $achievement = persistedAchievementFor($achievements);
    $userId = (string) Str::uuid();
    (new GrantAchievementHandler($achievements, $userAchievements))->handle(new GrantAchievementCommand($achievement->id()->value(), $userId, 'Evidencia'));
    (new GrantAchievementHandler($achievements, $userAchievements))->handle(new GrantAchievementCommand(persistedAchievementFor($achievements)->id()->value(), (string) Str::uuid(), 'Evidencia de otro usuario'));

    $responses = (new GetMyAchievementsHandler($userAchievements))->handle(new GetMyAchievementsQuery($userId));

    expect($responses)->toHaveCount(1)
        ->and($responses[0])->toBeInstanceOf(UserAchievementResponse::class)
        ->and($responses[0]->achievementId)->toBe($achievement->id()->value());
});
