<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Gamification\Domain\Aggregates\Achievement;
use Modules\Gamification\Domain\Aggregates\Badge;
use Modules\Gamification\Domain\Aggregates\Challenge;
use Modules\Gamification\Domain\Entities\ChallengeParticipation;
use Modules\Gamification\Domain\Entities\ExperienceEntry;
use Modules\Gamification\Domain\Entities\UserAchievement;
use Modules\Gamification\Domain\Entities\UserBadge;
use Modules\Gamification\Domain\Enums\BadgeCategory;
use Modules\Gamification\Domain\Enums\BadgeLevel;
use Modules\Gamification\Domain\Enums\ChallengeType;
use Modules\Gamification\Domain\Repositories\AchievementRepository;
use Modules\Gamification\Domain\Repositories\BadgeRepository;
use Modules\Gamification\Domain\Repositories\ChallengeParticipationRepository;
use Modules\Gamification\Domain\Repositories\ChallengeRepository;
use Modules\Gamification\Domain\Repositories\ExperienceEntryRepository;
use Modules\Gamification\Domain\Repositories\UserAchievementRepository;
use Modules\Gamification\Domain\Repositories\UserBadgeRepository;
use Modules\Gamification\Domain\ValueObjects\AchievementCode;
use Modules\Gamification\Domain\ValueObjects\AchievementId;
use Modules\Gamification\Domain\ValueObjects\BadgeCode;
use Modules\Gamification\Domain\ValueObjects\BadgeId;
use Modules\Gamification\Domain\ValueObjects\ChallengeCode;
use Modules\Gamification\Domain\ValueObjects\ChallengeId;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

function persistedGamificationWebUser(): User
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante Gamificado',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    return $user;
}

it('requiere autenticación para ver el panel de progreso', function (): void {
    /** @var TestCase $this */
    $this->get('/mi-progreso')->assertRedirect(route('login'));
});

it('muestra el estado inicial del panel para un estudiante sin actividad', function (): void {
    /** @var TestCase $this */
    $user = persistedGamificationWebUser();
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->get('/mi-progreso')
        ->assertOk()
        ->assertSeeText('Etapa de experiencia')
        ->assertSeeText('No hay tabla de posiciones')
        ->assertSeeText('Comenzá una experiencia de educación vial')
        ->assertSee(route('courses.index'))
        ->assertSeeText('0 XP')
        ->assertSeeText('Todavía no desbloqueaste logros')
        ->assertSeeText('Todavía no recibiste insignias')
        ->assertSeeText('Todavía no participás en retos');
});

it('muestra experiencia, logros, insignias y retos del estudiante', function (): void {
    /** @var TestCase $this */
    $user = persistedGamificationWebUser();
    $now = new DateTimeImmutable;

    $achievement = Achievement::create(
        id: AchievementId::fromString((string) Str::uuid()),
        code: AchievementCode::fromString('PRIMER-LOGRO-WEB'),
        name: 'Primer recorrido seguro',
        description: 'Completaste tu primer recorrido sin incidentes.',
        earningRule: 'Completar un recorrido seguro.',
    );
    app(AchievementRepository::class)->save($achievement);
    app(UserAchievementRepository::class)->save(UserAchievement::grant(
        id: (string) Str::uuid(),
        achievementId: $achievement->id()->value(),
        userId: $user->id(),
        evidence: 'Recorrido aprobado.',
        earnedAt: $now,
    ));

    $badge = Badge::create(
        id: BadgeId::fromString((string) Str::uuid()),
        code: BadgeCode::fromString('CONDUCTOR-WEB'),
        name: 'Conductor defensivo',
        description: 'Dominio inicial del manejo defensivo.',
        criteria: 'Superar la práctica defensiva.',
        category: BadgeCategory::Practical,
        level: BadgeLevel::Bronze,
    );
    app(BadgeRepository::class)->save($badge);
    app(UserBadgeRepository::class)->save(UserBadge::grant(
        id: (string) Str::uuid(),
        badgeId: $badge->id()->value(),
        userId: $user->id(),
        awardedVersion: 1,
        evidence: 'Práctica aprobada.',
        earnedAt: $now,
    ));

    $challenge = Challenge::create(
        id: ChallengeId::fromString((string) Str::uuid()),
        code: ChallengeCode::fromString('RETO-WEB'),
        name: 'Semana sin infracciones',
        description: 'Completá una semana de conducción segura.',
        type: ChallengeType::Individual,
        reward: '100 XP',
        startsAt: new DateTimeImmutable('-1 day'),
        endsAt: new DateTimeImmutable('+7 days'),
    );
    app(ChallengeRepository::class)->save($challenge);
    app(ChallengeParticipationRepository::class)->save(ChallengeParticipation::join(
        id: (string) Str::uuid(),
        challengeId: $challenge->id()->value(),
        userId: $user->id(),
        joinedAt: $now,
    ));

    app(ExperienceEntryRepository::class)->save(ExperienceEntry::record(
        id: (string) Str::uuid(),
        userId: $user->id(),
        points: 130,
        competencyId: 'manejo-defensivo',
        reason: 'Actividad completada.',
        recordedAt: $now,
    ));

    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->get('/mi-progreso')
        ->assertOk()
        ->assertSeeText('130 XP')
        ->assertSeeText('Primer recorrido seguro')
        ->assertSeeText('Conductor defensivo')
        ->assertSeeText('Semana sin infracciones')
        ->assertSeeText('100 XP')
        ->assertSeeText('Practicá una habilidad en un entorno seguro')
        ->assertSeeText('Bronce')
        ->assertSee(route('road-passport.show'));
});

it('permite que el estudiante se una una sola vez a un reto vigente', function (): void {
    /** @var TestCase $this */
    $user = persistedGamificationWebUser();
    $challenge = Challenge::create(
        id: ChallengeId::fromString((string) Str::uuid()),
        code: ChallengeCode::fromString('RETO-SEGURO-WEB'),
        name: 'Observar antes de decidir',
        description: 'Durante tres días identificá riesgos cotidianos desde lugares seguros.',
        type: ChallengeType::Individual,
        reward: 'Reconocimiento por constancia',
        startsAt: new DateTimeImmutable('-1 day'),
        endsAt: new DateTimeImmutable('+3 days'),
    );
    app(ChallengeRepository::class)->save($challenge);
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->get(route('gamification.dashboard'))
        ->assertOk()
        ->assertSeeText('Observar antes de decidir')
        ->assertSeeText('Unirme a este reto');

    $this->post(route('gamification.challenges.join', $challenge->id()->value()))
        ->assertRedirect(route('gamification.dashboard'))
        ->assertSessionHas('status');
    $this->post(route('gamification.challenges.join', $challenge->id()->value()))
        ->assertRedirect(route('gamification.dashboard'))
        ->assertSessionHas('error');

    expect(app(ChallengeParticipationRepository::class)->allForUser($user->id()))->toHaveCount(1);
    $this->get(route('gamification.dashboard'))->assertOk()->assertSeeText('Ya participás');
});
