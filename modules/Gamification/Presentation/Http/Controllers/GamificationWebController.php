<?php

declare(strict_types=1);

namespace Modules\Gamification\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\Academic\Domain\Repositories\CompetencyRepository;
use Modules\Foundation\Application\Bus\CommandBus;
use Modules\Foundation\Application\Bus\QueryBus;
use Modules\Foundation\Domain\Exceptions\DomainException;
use Modules\Gamification\Application\Commands\JoinChallengeCommand;
use Modules\Gamification\Application\Queries\GetMyAchievementsQuery;
use Modules\Gamification\Application\Queries\GetMyBadgesQuery;
use Modules\Gamification\Application\Queries\GetMyChallengeParticipationsQuery;
use Modules\Gamification\Application\Queries\GetMyExperienceSummaryQuery;
use Modules\Gamification\Application\Queries\ListAchievementsQuery;
use Modules\Gamification\Application\Queries\ListBadgesQuery;
use Modules\Gamification\Application\Queries\ListChallengesQuery;
use Modules\Gamification\Application\Responses\AchievementResponse;
use Modules\Gamification\Application\Responses\BadgeResponse;
use Modules\Gamification\Application\Responses\ChallengeParticipationResponse;
use Modules\Gamification\Application\Responses\ChallengeResponse;
use Modules\Gamification\Application\Responses\ExperienceSummaryResponse;
use Modules\Gamification\Application\Responses\UserAchievementResponse;
use Modules\Gamification\Application\Responses\UserBadgeResponse;
use Modules\RoadPassport\Domain\Enums\EvidenceType;
use Modules\RoadPassport\Domain\Repositories\RoadPassportRepository;

final class GamificationWebController
{
    public function __invoke(
        Request $request,
        QueryBus $queryBus,
        CompetencyRepository $competencies,
        RoadPassportRepository $passports,
    ): View {
        $userId = (string) $request->user()?->getAuthIdentifier();

        $experience = $queryBus->ask(new GetMyExperienceSummaryQuery(userId: $userId));
        $earnedAchievements = $queryBus->ask(new GetMyAchievementsQuery(userId: $userId));
        $earnedBadges = $queryBus->ask(new GetMyBadgesQuery(userId: $userId));
        $participations = $queryBus->ask(new GetMyChallengeParticipationsQuery(userId: $userId));
        $achievements = $queryBus->ask(new ListAchievementsQuery);
        $badges = $queryBus->ask(new ListBadgesQuery);
        $challenges = $queryBus->ask(new ListChallengesQuery);

        assert($experience instanceof ExperienceSummaryResponse);
        assert(is_array($earnedAchievements));
        assert(is_array($earnedBadges));
        assert(is_array($participations));
        assert(is_array($achievements));
        assert(is_array($badges));
        assert(is_array($challenges));

        $achievementCatalog = collect($achievements)->keyBy(
            static fn (AchievementResponse $achievement): string => $achievement->id,
        );
        $badgeCatalog = collect($badges)->keyBy(
            static fn (BadgeResponse $badge): string => $badge->id,
        );
        $challengeCatalog = collect($challenges)->keyBy(
            static fn (ChallengeResponse $challenge): string => $challenge->id,
        );
        $joinedChallengeIds = collect($participations)
            ->map(static fn (ChallengeParticipationResponse $participation): string => $participation->challengeId)
            ->all();

        $experienceData = $experience->toArray();
        $competencyCatalog = [];
        foreach ($competencies->all() as $competency) {
            $competencyCatalog[$competency->id()->value()] = [
                'title' => $competency->title(),
                'code' => $competency->code()->value(),
            ];
        }
        $experienceData['competencies'] = array_map(
            static function (array $item) use ($competencyCatalog): array {
                $label = $competencyCatalog[$item['competency_id']] ?? null;

                return array_merge($item, [
                    'competency_title' => $label['title'] ?? 'Competencia vial',
                    'competency_code' => $label['code'] ?? null,
                ]);
            },
            $experienceData['competencies'],
        );
        $passportEvidence = $passports->findByUserId($userId)?->evidence() ?? [];
        $hasObservedPractice = collect($passportEvidence)->contains(
            static fn ($item): bool => $item->type === EvidenceType::GuidedPracticeObserved,
        );
        $hasReflection = collect($passportEvidence)->contains(
            static fn ($item): bool => $item->type === EvidenceType::StudentReflection,
        );
        $nextStep = match (true) {
            $experienceData['total_points'] === 0 => [
                'eyebrow' => 'Tu primera misión',
                'title' => 'Comenzá una experiencia de educación vial',
                'text' => 'Elegí una lección, resolvé sus decisiones y explicá qué harías en una situación cotidiana.',
                'label' => 'Explorar cursos',
                'url' => route('courses.index'),
            ],
            ! $hasObservedPractice => [
                'eyebrow' => 'Del conocimiento a la acción',
                'title' => 'Practicá una habilidad en un entorno seguro',
                'text' => 'Usá una ficha de práctica apropiada para tu edad y, si corresponde, pedí acompañamiento adulto.',
                'label' => 'Ver mi Pasaporte Vial',
                'url' => route('road-passport.show'),
            ],
            ! $hasReflection => [
                'eyebrow' => 'Cerrá el ciclo',
                'title' => 'Reflexioná sobre lo que ocurrió',
                'text' => 'Contá qué aprendiste en la práctica y qué decisión segura aplicarás la próxima vez.',
                'label' => 'Responder en mi pasaporte',
                'url' => route('road-passport.show'),
            ],
            default => [
                'eyebrow' => 'Seguí ampliando tu recorrido',
                'title' => 'Explorá una competencia vial diferente',
                'text' => 'Elegí una experiencia nueva para construir un aprendizaje variado, útil y aplicable.',
                'label' => 'Continuar aprendiendo',
                'url' => route('courses.index'),
            ],
        };

        return view('gamification.dashboard', [
            'experience' => $experienceData,
            'nextStep' => $nextStep,
            'earnedAchievements' => array_map(
                static function (UserAchievementResponse $earned) use ($achievementCatalog): array {
                    $achievement = $achievementCatalog->get($earned->achievementId);

                    return array_merge($earned->toArray(), [
                        'name' => $achievement instanceof AchievementResponse ? $achievement->name : 'Logro',
                        'description' => $achievement instanceof AchievementResponse ? $achievement->description : '',
                        'earned_at_label' => Carbon::parse($earned->earnedAt)->timezone((string) config('app.timezone'))->settings(['locale' => 'es'])->translatedFormat('j \\d\\e F \\d\\e Y'),
                    ]);
                },
                $earnedAchievements,
            ),
            'earnedBadges' => array_map(
                static function (UserBadgeResponse $earned) use ($badgeCatalog): array {
                    $badge = $badgeCatalog->get($earned->badgeId);

                    return array_merge($earned->toArray(), [
                        'name' => $badge instanceof BadgeResponse ? $badge->name : 'Insignia',
                        'description' => $badge instanceof BadgeResponse ? $badge->description : '',
                        'level' => $badge instanceof BadgeResponse ? $badge->level : null,
                        'level_label' => match ($badge instanceof BadgeResponse ? $badge->level : null) {
                            'bronze' => 'Bronce',
                            'silver' => 'Plata',
                            'gold' => 'Oro',
                            default => null,
                        },
                    ]);
                },
                $earnedBadges,
            ),
            'participations' => array_map(
                static function (ChallengeParticipationResponse $participation) use ($challengeCatalog): array {
                    $challenge = $challengeCatalog->get($participation->challengeId);

                    return array_merge($participation->toArray(), [
                        'name' => $challenge instanceof ChallengeResponse ? $challenge->name : 'Reto',
                        'reward' => $challenge instanceof ChallengeResponse ? $challenge->reward : '',
                    ]);
                },
                $participations,
            ),
            'availableAchievements' => array_values(array_map(
                static fn (AchievementResponse $achievement): array => $achievement->toArray(),
                array_filter(
                    $achievements,
                    static fn (AchievementResponse $achievement): bool => $achievement->status === 'active',
                ),
            )),
            'availableBadges' => array_values(array_map(
                static fn (BadgeResponse $badge): array => $badge->toArray(),
                array_filter($badges, static fn (BadgeResponse $badge): bool => $badge->status === 'active'),
            )),
            'availableChallenges' => array_values(array_map(
                static function (ChallengeResponse $challenge) use ($joinedChallengeIds): array {
                    $startsAt = Carbon::parse($challenge->startsAt);
                    $endsAt = Carbon::parse($challenge->endsAt);
                    $alreadyJoined = in_array($challenge->id, $joinedChallengeIds, true);

                    return array_merge($challenge->toArray(), [
                        'already_joined' => $alreadyJoined,
                        'can_join' => ! $alreadyJoined && Carbon::now()->betweenIncluded($startsAt, $endsAt),
                        'starts_at_label' => $startsAt->timezone((string) config('app.timezone'))->settings(['locale' => 'es'])->translatedFormat('j \\d\\e F'),
                        'ends_at_label' => $endsAt->timezone((string) config('app.timezone'))->settings(['locale' => 'es'])->translatedFormat('j \\d\\e F \\d\\e Y'),
                    ]);
                },
                array_filter($challenges, static fn (ChallengeResponse $challenge): bool => $challenge->status === 'active'),
            )),
        ]);
    }

    public function join(string $challengeId, Request $request, CommandBus $commandBus): RedirectResponse
    {
        try {
            $commandBus->dispatch(new JoinChallengeCommand(
                challengeId: $challengeId,
                userId: (string) $request->user()?->getAuthIdentifier(),
            ));
        } catch (DomainException $exception) {
            return redirect()->route('gamification.dashboard')->with('error', $exception->getMessage());
        }

        return redirect()->route('gamification.dashboard')
            ->with('status', 'Te uniste al reto. Avanzá con calma y registrá únicamente experiencias reales y seguras.');
    }
}
