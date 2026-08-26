<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Academic\Domain\Aggregates\Enrollment;
use Modules\Academic\Domain\Enums\EnrollmentSource;
use Modules\Academic\Domain\Enums\EnrollmentStatus;
use Modules\Academic\Domain\Repositories\EnrollmentRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\EnrollmentId;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\EnrollmentModel;
use Modules\Organization\Domain\ValueObjects\OrganizationId;

final readonly class EloquentEnrollmentRepository implements EnrollmentRepository
{
    public function save(Enrollment $enrollment): void
    {
        EnrollmentModel::query()->updateOrCreate(
            ['id' => $enrollment->id()->value()],
            [
                'course_id' => $enrollment->courseId()->value(),
                'user_id' => $enrollment->userId(),
                'organization_id' => $enrollment->organizationId()?->value(),
                'status' => $enrollment->status()->value,
                'source' => $enrollment->source()->value,
                'starts_at' => $enrollment->startsAt(),
                'ends_at' => $enrollment->endsAt(),
                'enrolled_at' => $enrollment->enrolledAt(),
            ],
        );
    }

    public function findById(EnrollmentId $id): ?Enrollment
    {
        $model = EnrollmentModel::query()->where('id', $id->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function findActiveOrPendingFor(CourseId $courseId, string $userId): ?Enrollment
    {
        $model = EnrollmentModel::query()
            ->where('course_id', $courseId->value())
            ->where('user_id', $userId)
            ->whereIn('status', [EnrollmentStatus::Pending->value, EnrollmentStatus::Active->value])
            ->orderBy('created_at')
            ->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function all(
        ?CourseId $courseId = null,
        ?string $userId = null,
        ?string $organizationId = null,
        ?EnrollmentStatus $status = null,
        ?EnrollmentSource $source = null,
    ): array {
        $builder = EnrollmentModel::query();

        if ($courseId !== null) {
            $builder->where('course_id', $courseId->value());
        }

        if ($userId !== null) {
            $builder->where('user_id', $userId);
        }

        if ($organizationId !== null) {
            $builder->where('organization_id', strtolower(trim($organizationId)));
        }

        if ($status !== null) {
            $builder->where('status', $status->value);
        }

        if ($source !== null) {
            $builder->where('source', $source->value);
        }

        return array_values($builder->orderBy('created_at')->get()
            ->map(fn (EnrollmentModel $model): Enrollment => $this->toDomain($model))
            ->all());
    }

    private function toDomain(EnrollmentModel $model): Enrollment
    {
        return Enrollment::restore(
            EnrollmentId::fromString((string) $model->getAttribute('id')),
            CourseId::fromString((string) $model->getAttribute('course_id')),
            (string) $model->getAttribute('user_id'),
            $model->getAttribute('organization_id') === null
                ? null
                : OrganizationId::fromString((string) $model->getAttribute('organization_id')),
            EnrollmentStatus::from((string) $model->getAttribute('status')),
            EnrollmentSource::from((string) $model->getAttribute('source')),
            $model->getAttribute('starts_at'),
            $model->getAttribute('ends_at'),
            $model->getAttribute('enrolled_at'),
        );
    }
}
