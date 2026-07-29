<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use DateTimeInterface;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Enums\CourseStatus;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\CourseModel;

final class EloquentCourseRepository implements CourseRepository
{
    public function save(Course $course): void
    {
        CourseModel::query()->updateOrCreate(
            [
                'id' => $course->id()->value(),
            ],
            [
                'code' => $course->code()->value(),
                'title' => $course->title()->value(),
                'description' => $course->description(),
                'status' => $course->status()->value,
                'published_at' => $course->publishedAt(),
                'archived_at' => $course->archivedAt(),
            ],
        );
    }

    public function findById(CourseId $id): ?Course
    {
        $model = CourseModel::query()->find($id->value());

        return $model === null
            ? null
            : $this->toDomain($model);
    }

    public function findByCode(CourseCode $code): ?Course
    {
        $model = CourseModel::query()
            ->where('code', $code->value())
            ->first();

        return $model === null
            ? null
            : $this->toDomain($model);
    }

    public function existsByCode(CourseCode $code): bool
    {
        return CourseModel::query()
            ->where('code', $code->value())
            ->exists();
    }

    private function toDomain(CourseModel $model): Course
    {
        return Course::restore(
            id: CourseId::fromString((string) $model->getAttribute('id')),
            code: CourseCode::fromString((string) $model->getAttribute('code')),
            title: CourseTitle::fromString((string) $model->getAttribute('title')),
            description: $this->nullableString(
                $model->getAttribute('description'),
            ),
            status: CourseStatus::from(
                (string) $model->getAttribute('status'),
            ),
            publishedAt: $this->toDateTimeImmutable(
                $model->getAttribute('published_at'),
            ),
            archivedAt: $this->toDateTimeImmutable(
                $model->getAttribute('archived_at'),
            ),
        );
    }

    private function nullableString(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    private function toDateTimeImmutable(mixed $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        return new DateTimeImmutable((string) $value);
    }
}
