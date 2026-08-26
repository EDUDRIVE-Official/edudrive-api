<?php

declare(strict_types=1);

namespace Modules\Gamification\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Gamification\Domain\Entities\ChallengeParticipation;
use Modules\Gamification\Domain\Enums\ChallengeParticipationStatus;
use Modules\Gamification\Domain\Repositories\ChallengeParticipationRepository;
use Modules\Gamification\Infrastructure\Persistence\Eloquent\Models\ChallengeParticipationModel;

final readonly class EloquentChallengeParticipationRepository implements ChallengeParticipationRepository
{
    public function save(ChallengeParticipation $participation): void
    {
        ChallengeParticipationModel::query()->updateOrCreate(
            ['id' => $participation->id()],
            [
                'challenge_id' => $participation->challengeId(),
                'user_id' => $participation->userId(),
                'status' => $participation->status()->value,
                'joined_at' => $participation->joinedAt(),
                'completed_at' => $participation->completedAt(),
                'evidence' => $participation->evidence(),
            ],
        );
    }

    public function findByChallengeAndUser(string $challengeId, string $userId): ?ChallengeParticipation
    {
        $model = ChallengeParticipationModel::query()
            ->where('challenge_id', $challengeId)
            ->where('user_id', $userId)
            ->first();

        return $model === null ? null : $this->toDomain($model);
    }

    /** @return list<ChallengeParticipation> */
    public function allForUser(string $userId): array
    {
        return array_values(
            ChallengeParticipationModel::query()
                ->where('user_id', $userId)
                ->orderBy('joined_at')
                ->get()
                ->map(fn (ChallengeParticipationModel $model): ChallengeParticipation => $this->toDomain($model))
                ->all(),
        );
    }

    private function toDomain(ChallengeParticipationModel $model): ChallengeParticipation
    {
        $completedAt = $model->getAttribute('completed_at');
        $evidence = $model->getAttribute('evidence');

        return ChallengeParticipation::restore(
            id: (string) $model->getAttribute('id'),
            challengeId: (string) $model->getAttribute('challenge_id'),
            userId: (string) $model->getAttribute('user_id'),
            status: ChallengeParticipationStatus::from((string) $model->getAttribute('status')),
            joinedAt: new DateTimeImmutable((string) $model->getAttribute('joined_at')),
            completedAt: $completedAt === null ? null : new DateTimeImmutable((string) $completedAt),
            evidence: $evidence === null ? null : (string) $evidence,
        );
    }
}
