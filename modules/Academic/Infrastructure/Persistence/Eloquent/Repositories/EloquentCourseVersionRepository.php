<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Modules\Academic\Domain\Entities\CourseVersion;
use Modules\Academic\Domain\Enums\CourseVersionStatus;
use Modules\Academic\Domain\Repositories\CourseVersionRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\CourseVersionModel;

final readonly class EloquentCourseVersionRepository implements CourseVersionRepository
{
    public function save(CourseVersion $version): void
    {
        CourseVersionModel::query()->create([
            'id' => $version->id(),
            'course_id' => $version->courseId()->value(),
            'version_number' => $version->versionNumber(),
            'status' => $version->status()->value,
            'snapshot' => $version->snapshot(),
            'published_at' => $version->publishedAt(),
            'archived_at' => $version->archivedAt(),
        ]);
    }

    /** @return list<CourseVersion> */
    public function allForCourse(CourseId $courseId): array
    {
        return array_values(
            $this->queryForCourse($courseId)
                ->orderBy('version_number')
                ->get()
                ->map(fn (CourseVersionModel $model): CourseVersion => $this->toDomain($model))
                ->all(),
        );
    }

    public function findByNumber(CourseId $courseId, int $versionNumber): ?CourseVersion
    {
        $model = $this->queryForCourse($courseId)
            ->where('version_number', $versionNumber)
            ->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function nextVersionNumber(CourseId $courseId): int
    {
        $last = $this->queryForCourse($courseId)
            ->max('version_number');

        return ((int) $last) + 1;
    }

    /** @return Builder<CourseVersionModel> */
    private function queryForCourse(CourseId $courseId): Builder
    {
        return CourseVersionModel::query()->where('course_id', $courseId->value());
    }

    private function toDomain(CourseVersionModel $model): CourseVersion
    {
        /** @var array<string, mixed> $snapshot */
        $snapshot = $model->getAttribute('snapshot');

        return CourseVersion::restore(
            id: (string) $model->getAttribute('id'),
            courseId: CourseId::fromString((string) $model->getAttribute('course_id')),
            versionNumber: (int) $model->getAttribute('version_number'),
            status: CourseVersionStatus::from((string) $model->getAttribute('status')),
            snapshot: $snapshot,
            publishedAt: $this->toDateTimeImmutable($model->getAttribute('published_at')),
            archivedAt: $this->toNullableDateTimeImmutable($model->getAttribute('archived_at')),
        );
    }

    private function toDateTimeImmutable(mixed $value): DateTimeImmutable
    {
        if ($value instanceof \DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        return new DateTimeImmutable((string) $value);
    }

    private function toNullableDateTimeImmutable(mixed $value): ?DateTimeImmutable
    {
        return $value === null ? null : $this->toDateTimeImmutable($value);
    }
}
