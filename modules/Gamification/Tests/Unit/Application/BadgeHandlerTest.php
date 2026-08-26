<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Gamification\Application\Commands\CreateBadgeCommand;
use Modules\Gamification\Application\Commands\GrantBadgeCommand;
use Modules\Gamification\Application\Commands\RetireBadgeCommand;
use Modules\Gamification\Application\Commands\UpdateBadgeCommand;
use Modules\Gamification\Application\Exceptions\BadgeAlreadyExists;
use Modules\Gamification\Application\Exceptions\BadgeAlreadyGranted;
use Modules\Gamification\Application\Exceptions\BadgeNotAvailable;
use Modules\Gamification\Application\Exceptions\BadgeNotFound;
use Modules\Gamification\Application\Queries\GetBadgeQuery;
use Modules\Gamification\Application\Queries\GetMyBadgesQuery;
use Modules\Gamification\Application\Queries\ListBadgesQuery;
use Modules\Gamification\Application\Responses\BadgeResponse;
use Modules\Gamification\Application\Responses\UserBadgeResponse;
use Modules\Gamification\Application\UseCases\CreateBadgeHandler;
use Modules\Gamification\Application\UseCases\GetBadgeHandler;
use Modules\Gamification\Application\UseCases\GetMyBadgesHandler;
use Modules\Gamification\Application\UseCases\GrantBadgeHandler;
use Modules\Gamification\Application\UseCases\ListBadgesHandler;
use Modules\Gamification\Application\UseCases\RetireBadgeHandler;
use Modules\Gamification\Application\UseCases\UpdateBadgeHandler;
use Modules\Gamification\Domain\Aggregates\Badge;
use Modules\Gamification\Domain\Entities\UserBadge;
use Modules\Gamification\Domain\Enums\BadgeCategory;
use Modules\Gamification\Domain\Enums\BadgeLevel;
use Modules\Gamification\Domain\Exceptions\InvalidBadgeTransition;
use Modules\Gamification\Domain\Repositories\BadgeRepository;
use Modules\Gamification\Domain\Repositories\UserBadgeRepository;
use Modules\Gamification\Domain\ValueObjects\BadgeCode;
use Modules\Gamification\Domain\ValueObjects\BadgeId;

final class InMemoryBadgeRepository implements BadgeRepository
{
    /** @var array<string, Badge> */
    public array $items = [];

    public function save(Badge $badge): void
    {
        $this->items[$badge->id()->value()] = $badge;
    }

    public function findById(BadgeId $id): ?Badge
    {
        return $this->items[$id->value()] ?? null;
    }

    public function findByCode(BadgeCode $code): ?Badge
    {
        foreach ($this->items as $badge) {
            if ($badge->code()->equals($code)) {
                return $badge;
            }
        }

        return null;
    }

    /** @return list<Badge> */
    public function all(): array
    {
        return array_values($this->items);
    }
}

final class InMemoryUserBadgeRepository implements UserBadgeRepository
{
    /** @var list<UserBadge> */
    public array $items = [];

    public function save(UserBadge $userBadge): void
    {
        $this->items[] = $userBadge;
    }

    public function findByBadgeAndUser(string $badgeId, string $userId): ?UserBadge
    {
        foreach ($this->items as $item) {
            if ($item->badgeId() === $badgeId && $item->userId() === $userId) {
                return $item;
            }
        }

        return null;
    }

    /** @return list<UserBadge> */
    public function allForUser(string $userId): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (UserBadge $item): bool => $item->userId() === $userId,
        ));
    }
}

function persistedBadgeFor(InMemoryBadgeRepository $repository, ?string $code = null): Badge
{
    $badge = Badge::create(
        id: BadgeId::fromString((string) Str::uuid()),
        code: BadgeCode::fromString($code ?? 'INSIGNIA-'.strtoupper((string) Str::random(6))),
        name: 'Conductor defensivo',
        description: 'Se otorga por demostrar manejo defensivo consistente.',
        criteria: 'Completar 10 sesiones prácticas sin infracciones.',
        category: BadgeCategory::Practical,
        level: BadgeLevel::Bronze,
    );
    $repository->save($badge);

    return $badge;
}

it('crea una insignia nueva', function (): void {
    $badges = new InMemoryBadgeRepository;

    $response = (new CreateBadgeHandler($badges))->handle(new CreateBadgeCommand(
        code: 'conductor-defensivo',
        name: 'Conductor defensivo',
        description: 'Se otorga por demostrar manejo defensivo consistente.',
        criteria: 'Completar 10 sesiones prácticas sin infracciones.',
        category: 'practical',
        level: 'bronze',
    ));

    expect($response)->toBeInstanceOf(BadgeResponse::class)
        ->and($response->code)->toBe('CONDUCTOR-DEFENSIVO')
        ->and($response->version)->toBe(1)
        ->and($response->status)->toBe('active');
});

it('rechaza crear una insignia con un codigo duplicado', function (): void {
    $badges = new InMemoryBadgeRepository;
    $badge = persistedBadgeFor($badges);

    expect(fn () => (new CreateBadgeHandler($badges))->handle(new CreateBadgeCommand(
        code: $badge->code()->value(),
        name: 'Otro nombre',
        description: 'Otra descripcion',
        criteria: 'Otro criterio',
        category: 'educational',
        level: 'silver',
    )))->toThrow(BadgeAlreadyExists::class);
});

it('actualiza el contenido de una insignia e incrementa su version', function (): void {
    $badges = new InMemoryBadgeRepository;
    $badge = persistedBadgeFor($badges);

    $response = (new UpdateBadgeHandler($badges))->handle(new UpdateBadgeCommand(
        badgeId: $badge->id()->value(),
        name: 'Conductor defensivo avanzado',
        description: 'Descripcion actualizada.',
        criteria: 'Completar 20 sesiones prácticas sin infracciones.',
        category: 'practical',
        level: 'silver',
    ));

    expect($response->version)->toBe(2)
        ->and($response->name)->toBe('Conductor defensivo avanzado')
        ->and($response->level)->toBe('silver');
});

it('rechaza actualizar una insignia inexistente', function (): void {
    $badges = new InMemoryBadgeRepository;

    expect(fn () => (new UpdateBadgeHandler($badges))->handle(new UpdateBadgeCommand((string) Str::uuid(), 'Nombre', 'Descripcion', 'Criterio', 'practical', 'gold')))
        ->toThrow(BadgeNotFound::class);
});

it('rechaza actualizar el contenido de una insignia retirada', function (): void {
    $badges = new InMemoryBadgeRepository;
    $badge = persistedBadgeFor($badges);
    (new RetireBadgeHandler($badges))->handle(new RetireBadgeCommand($badge->id()->value()));

    expect(fn () => (new UpdateBadgeHandler($badges))->handle(new UpdateBadgeCommand($badge->id()->value(), 'Nombre', 'Descripcion', 'Criterio', 'practical', 'gold')))
        ->toThrow(InvalidBadgeTransition::class);
});

it('retira una insignia existente', function (): void {
    $badges = new InMemoryBadgeRepository;
    $badge = persistedBadgeFor($badges);

    $response = (new RetireBadgeHandler($badges))->handle(new RetireBadgeCommand($badge->id()->value(), 'Ya no aplica'));

    expect($response->status)->toBe('retired');
});

it('rechaza retirar una insignia inexistente', function (): void {
    $badges = new InMemoryBadgeRepository;

    expect(fn () => (new RetireBadgeHandler($badges))->handle(new RetireBadgeCommand((string) Str::uuid())))
        ->toThrow(BadgeNotFound::class);
});

it('propaga el rechazo de dominio al retirar dos veces', function (): void {
    $badges = new InMemoryBadgeRepository;
    $badge = persistedBadgeFor($badges);
    (new RetireBadgeHandler($badges))->handle(new RetireBadgeCommand($badge->id()->value()));

    expect(fn () => (new RetireBadgeHandler($badges))->handle(new RetireBadgeCommand($badge->id()->value())))
        ->toThrow(InvalidBadgeTransition::class);
});

it('otorga una insignia activa a un usuario con su version vigente', function (): void {
    $badges = new InMemoryBadgeRepository;
    $userBadges = new InMemoryUserBadgeRepository;
    $badge = persistedBadgeFor($badges);
    $userId = (string) Str::uuid();

    $response = (new GrantBadgeHandler($badges, $userBadges))->handle(new GrantBadgeCommand(
        badgeId: $badge->id()->value(),
        userId: $userId,
        evidence: 'Completó 10 sesiones prácticas sin infracciones.',
    ));

    expect($response)->toBeInstanceOf(UserBadgeResponse::class)
        ->and($response->badgeId)->toBe($badge->id()->value())
        ->and($response->awardedVersion)->toBe(1)
        ->and($response->evidence)->toBe('Completó 10 sesiones prácticas sin infracciones.');
});

it('rechaza otorgar una insignia inexistente', function (): void {
    $badges = new InMemoryBadgeRepository;
    $userBadges = new InMemoryUserBadgeRepository;

    expect(fn () => (new GrantBadgeHandler($badges, $userBadges))->handle(new GrantBadgeCommand((string) Str::uuid(), (string) Str::uuid(), 'Evidencia')))
        ->toThrow(BadgeNotFound::class);
});

it('rechaza otorgar una insignia retirada', function (): void {
    $badges = new InMemoryBadgeRepository;
    $userBadges = new InMemoryUserBadgeRepository;
    $badge = persistedBadgeFor($badges);
    $badge->retire(null, new DateTimeImmutable('now'));
    $badges->save($badge);

    expect(fn () => (new GrantBadgeHandler($badges, $userBadges))->handle(new GrantBadgeCommand($badge->id()->value(), (string) Str::uuid(), 'Evidencia')))
        ->toThrow(BadgeNotAvailable::class);
});

it('rechaza otorgar la misma insignia dos veces al mismo usuario', function (): void {
    $badges = new InMemoryBadgeRepository;
    $userBadges = new InMemoryUserBadgeRepository;
    $badge = persistedBadgeFor($badges);
    $userId = (string) Str::uuid();
    (new GrantBadgeHandler($badges, $userBadges))->handle(new GrantBadgeCommand($badge->id()->value(), $userId, 'Primera vez'));

    expect(fn () => (new GrantBadgeHandler($badges, $userBadges))->handle(new GrantBadgeCommand($badge->id()->value(), $userId, 'Segunda vez')))
        ->toThrow(BadgeAlreadyGranted::class);
});

it('consulta una insignia por id', function (): void {
    $badges = new InMemoryBadgeRepository;
    $badge = persistedBadgeFor($badges);

    $response = (new GetBadgeHandler($badges))->handle(new GetBadgeQuery($badge->id()->value()));

    expect($response->id)->toBe($badge->id()->value());
});

it('rechaza consultar una insignia inexistente', function (): void {
    $badges = new InMemoryBadgeRepository;

    expect(fn () => (new GetBadgeHandler($badges))->handle(new GetBadgeQuery((string) Str::uuid())))
        ->toThrow(BadgeNotFound::class);
});

it('lista todas las insignias del catalogo', function (): void {
    $badges = new InMemoryBadgeRepository;
    persistedBadgeFor($badges);
    persistedBadgeFor($badges);

    $responses = (new ListBadgesHandler($badges))->handle(new ListBadgesQuery);

    expect($responses)->toHaveCount(2)
        ->and($responses[0])->toBeInstanceOf(BadgeResponse::class);
});

it('lista las insignias obtenidas por el usuario autenticado', function (): void {
    $badges = new InMemoryBadgeRepository;
    $userBadges = new InMemoryUserBadgeRepository;
    $badge = persistedBadgeFor($badges);
    $userId = (string) Str::uuid();
    (new GrantBadgeHandler($badges, $userBadges))->handle(new GrantBadgeCommand($badge->id()->value(), $userId, 'Evidencia'));
    (new GrantBadgeHandler($badges, $userBadges))->handle(new GrantBadgeCommand(persistedBadgeFor($badges)->id()->value(), (string) Str::uuid(), 'Evidencia de otro usuario'));

    $responses = (new GetMyBadgesHandler($userBadges))->handle(new GetMyBadgesQuery($userId));

    expect($responses)->toHaveCount(1)
        ->and($responses[0])->toBeInstanceOf(UserBadgeResponse::class)
        ->and($responses[0]->badgeId)->toBe($badge->id()->value());
});
