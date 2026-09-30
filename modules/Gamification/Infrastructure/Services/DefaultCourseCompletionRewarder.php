<?php

declare(strict_types=1);

namespace Modules\Gamification\Infrastructure\Services;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Gamification\Application\Services\CourseCompletionRewarder;
use Modules\Gamification\Domain\Aggregates\Badge;
use Modules\Gamification\Domain\Entities\ExperienceEntry;
use Modules\Gamification\Domain\Entities\UserBadge;
use Modules\Gamification\Domain\Enums\BadgeCategory;
use Modules\Gamification\Domain\Enums\BadgeLevel;
use Modules\Gamification\Domain\Repositories\BadgeRepository;
use Modules\Gamification\Domain\Repositories\ExperienceEntryRepository;
use Modules\Gamification\Domain\Repositories\UserBadgeRepository;
use Modules\Gamification\Domain\ValueObjects\BadgeCode;
use Modules\Gamification\Domain\ValueObjects\BadgeId;

final readonly class DefaultCourseCompletionRewarder implements CourseCompletionRewarder
{
    private const string FIRST_MISSION_BADGE = 'PRIMERA-MISION';

    public function __construct(
        private ExperienceEntryRepository $experienceEntries,
        private BadgeRepository $badges,
        private UserBadgeRepository $userBadges,
        private CourseRepository $courses,
    ) {}

    public function reward(string $userId, string $courseId, ?string $competencyId): void
    {
        $reason = 'Misión completada: '.$courseId;
        $alreadyRewarded = false;
        foreach ($this->experienceEntries->allForUser($userId) as $entry) {
            if ($entry->reason() === $reason) {
                $alreadyRewarded = true;
                break;
            }
        }

        if (! $alreadyRewarded) {
            $this->experienceEntries->save(ExperienceEntry::record(
                id: (string) Str::uuid(),
                userId: $userId,
                points: 100,
                competencyId: $competencyId,
                reason: $reason,
                recordedAt: new DateTimeImmutable('now'),
            ));
        }

        $this->grantBadge(
            userId: $userId,
            courseId: $courseId,
            code: self::FIRST_MISSION_BADGE,
            name: 'Primera misión cumplida',
            description: 'Reconoce la primera experiencia completa de educación vial en EDUDRIVE.',
            criteria: 'Completar por primera vez todas las lecciones y prácticas de un curso.',
        );

        $course = $this->courses->findById(CourseId::fromString($courseId));
        if ($course !== null) {
            $this->grantBadge(
                userId: $userId,
                courseId: $courseId,
                code: 'CURSO-'.$course->code()->value(),
                name: 'Misión completada: '.$course->title()->value(),
                description: 'Reconoce la finalización integral de este recorrido de educación vial.',
                criteria: 'Completar todas las lecciones y decisiones formativas de '.$course->title()->value().'.',
            );
        }
    }

    private function grantBadge(string $userId, string $courseId, string $code, string $name, string $description, string $criteria): void
    {
        $badge = $this->badges->findByCode(BadgeCode::fromString($code));
        if ($badge === null) {
            $badge = Badge::create(
                id: BadgeId::fromString((string) Str::uuid()),
                code: BadgeCode::fromString($code),
                name: $name,
                description: $description,
                criteria: $criteria,
                category: BadgeCategory::Educational,
                level: BadgeLevel::Bronze,
            );
            $this->badges->save($badge);
        }

        if ($this->userBadges->findByBadgeAndUser($badge->id()->value(), $userId) === null) {
            $this->userBadges->save(UserBadge::grant(
                id: (string) Str::uuid(),
                badgeId: $badge->id()->value(),
                userId: $userId,
                awardedVersion: $badge->version(),
                evidence: 'Curso completado: '.$courseId,
                earnedAt: new DateTimeImmutable('now'),
            ));
        }
    }
}
