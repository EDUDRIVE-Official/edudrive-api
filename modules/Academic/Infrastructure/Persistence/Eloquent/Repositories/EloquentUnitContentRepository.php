<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Application\Exceptions\CourseContentIdConflict;
use Modules\Academic\Application\Exceptions\CourseUnitNotFound;
use Modules\Academic\Domain\Aggregates\UnitContent;
use Modules\Academic\Domain\Entities\Lesson;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\Repositories\UnitContentRepository;
use Modules\Academic\Domain\Services\ContentBlockFactory;
use Modules\Academic\Domain\ValueObjects\ContentBlockId;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;
use Modules\Academic\Domain\ValueObjects\CurriculumCode;
use Modules\Academic\Domain\ValueObjects\LessonId;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\ContentBlockModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\CourseModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\LessonModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\UnitContentModel;

final readonly class EloquentUnitContentRepository implements UnitContentRepository
{
    public function __construct(private CourseRepository $courses) {}

    public function findForCourseUnit(CourseId $courseId, CourseUnitId $unitId): ?UnitContent
    {
        if (! CourseModel::query()->whereKey($courseId->value())->exists()) {
            return null;
        }

        $ownsUnit = DB::table('academic_course_units as units')
            ->join('academic_course_modules as modules', 'modules.id', '=', 'units.module_id')
            ->where('units.id', $unitId->value())
            ->where('modules.course_id', $courseId->value())
            ->exists();

        if (! $ownsUnit) {
            throw CourseUnitNotFound::create();
        }

        $model = $this->queryContent()->find($unitId->value());

        return $model === null ? UnitContent::create($unitId, []) : $this->toDomain($model);
    }

    public function replaceAtomically(CourseId $courseId, CourseUnitId $unitId, UnitContent $content): ?UnitContent
    {
        try {
            return DB::transaction(function () use ($courseId, $unitId, $content): ?UnitContent {
                $locked = CourseModel::query()->whereKey($courseId->value())->lockForUpdate()->first();

                if ($locked === null) {
                    return null;
                }

                $course = $this->courses->findById($courseId);
                $course?->ensureContentCanBeModified();

                if ($course === null || ! $course->ownsUnit($unitId) || ! $content->unitId()->equals($unitId)) {
                    throw CourseUnitNotFound::create();
                }

                $this->assertIdsBelongToUnit($unitId, $content->lessons());
                UnitContentModel::query()->firstOrCreate(['unit_id' => $unitId->value()]);
                $this->moveExistingToTemporaryValues($unitId);
                $this->sync($unitId, $content->lessons());

                $canonical = $this->queryContent()->findOrFail($unitId->value());

                return $this->toDomain($canonical);
            });
        } catch (QueryException $exception) {
            if ($this->isContentIdUniqueViolation($exception)) {
                throw CourseContentIdConflict::create();
            }

            throw $exception;
        }
    }

    /** @param list<Lesson> $lessons */
    private function assertIdsBelongToUnit(CourseUnitId $unitId, array $lessons): void
    {
        $lessonIds = array_map(static fn (Lesson $lesson): string => $lesson->id()->value(), $lessons);
        $blockIds = [];

        foreach ($lessons as $lesson) {
            foreach ($lesson->blocks() as $block) {
                $blockIds[] = $block->id()->value();
            }
        }

        $foreignLesson = $lessonIds !== [] && LessonModel::query()
            ->whereIn('id', $lessonIds)->where('unit_id', '!=', $unitId->value())->exists();
        $foreignBlock = $blockIds !== [] && DB::table('academic_lesson_blocks as blocks')
            ->join('academic_lessons as lessons', 'lessons.id', '=', 'blocks.lesson_id')
            ->whereIn('blocks.id', $blockIds)->where('lessons.unit_id', '!=', $unitId->value())->exists();

        if ($foreignLesson || $foreignBlock) {
            throw CourseContentIdConflict::create();
        }
    }

    private function moveExistingToTemporaryValues(CourseUnitId $unitId): void
    {
        $lessons = LessonModel::query()->where('unit_id', $unitId->value())->orderBy('id')->get();

        foreach ($lessons as $index => $lesson) {
            $lesson->update(['code' => '__TMP__'.$lesson->getKey(), 'position' => -1 - $index]);
        }

        foreach (ContentBlockModel::query()->whereIn('lesson_id', $lessons->modelKeys())->orderBy('id')->get() as $index => $block) {
            $block->update(['position' => -1 - $index]);
        }
    }

    /** @param list<Lesson> $lessons */
    private function sync(CourseUnitId $unitId, array $lessons): void
    {
        foreach ($lessons as $lessonIndex => $lesson) {
            $lessonAttributes = [
                'unit_id' => $unitId->value(), 'code' => '__IN__'.$lesson->id()->value(),
                'title' => $lesson->title(), 'summary' => $lesson->summary(),
                'duration_minutes' => $lesson->durationMinutes(), 'position' => -1000000 - $lessonIndex,
            ];
            $updated = LessonModel::query()->whereKey($lesson->id()->value())->where('unit_id', $unitId->value())->update($lessonAttributes);

            if ($updated === 0) {
                LessonModel::query()->create(['id' => $lesson->id()->value(), ...$lessonAttributes]);
            }

            foreach ($lesson->blocks() as $blockIndex => $block) {
                $attributes = [
                    'lesson_id' => $lesson->id()->value(), 'type' => $block->type()->value,
                    'position' => -2000000 - $blockIndex, 'payload' => $block->payload(),
                ];
                $ownLessonIds = LessonModel::query()->select('id')->where('unit_id', $unitId->value());
                $blockUpdated = ContentBlockModel::query()->whereKey($block->id()->value())
                    ->whereIn('lesson_id', $ownLessonIds)->update($attributes);

                if ($blockUpdated === 0) {
                    ContentBlockModel::query()->create(['id' => $block->id()->value(), ...$attributes]);
                }
            }
        }

        $incomingLessonIds = array_map(static fn (Lesson $lesson): string => $lesson->id()->value(), $lessons);
        $incomingBlockIds = [];
        foreach ($lessons as $lesson) {
            foreach ($lesson->blocks() as $block) {
                $incomingBlockIds[] = $block->id()->value();
            }
        }

        $unitLessonIds = LessonModel::query()->select('id')->where('unit_id', $unitId->value());
        $obsoleteBlocks = ContentBlockModel::query()->whereIn('lesson_id', $unitLessonIds);
        if ($incomingBlockIds !== []) {
            $obsoleteBlocks->whereNotIn('id', $incomingBlockIds);
        }
        $obsoleteBlocks->delete();

        $obsoleteLessons = LessonModel::query()->where('unit_id', $unitId->value());
        if ($incomingLessonIds !== []) {
            $obsoleteLessons->whereNotIn('id', $incomingLessonIds);
        }
        $obsoleteLessons->delete();

        foreach ($lessons as $lesson) {
            LessonModel::query()->whereKey($lesson->id()->value())->update([
                'code' => $lesson->code()->value(), 'position' => $lesson->position(),
            ]);
            foreach ($lesson->blocks() as $block) {
                ContentBlockModel::query()->whereKey($block->id()->value())->update(['position' => $block->position()]);
            }
        }
    }

    /** @return Builder<UnitContentModel> */
    private function queryContent(): Builder
    {
        return UnitContentModel::query()->with('lessons.blocks');
    }

    private function toDomain(UnitContentModel $model): UnitContent
    {
        $lessons = $model->lessons->map(function (LessonModel $lesson): Lesson {
            $blocks = $lesson->blocks->map(static function (ContentBlockModel $block) {
                /** @var array<string, mixed> $payload */
                $payload = $block->getAttribute('payload');

                return ContentBlockFactory::create(
                    ContentBlockId::fromString((string) $block->getKey()),
                    (string) $block->getAttribute('type'),
                    (int) $block->getAttribute('position'),
                    $payload,
                );
            })->all();

            return Lesson::create(
                LessonId::fromString((string) $lesson->getKey()),
                CurriculumCode::fromString((string) $lesson->getAttribute('code')),
                (string) $lesson->getAttribute('title'),
                $lesson->getAttribute('summary') === null ? null : (string) $lesson->getAttribute('summary'),
                $lesson->getAttribute('duration_minutes') === null ? null : (int) $lesson->getAttribute('duration_minutes'),
                (int) $lesson->getAttribute('position'),
                array_values($blocks),
            );
        })->all();

        return UnitContent::create(
            CourseUnitId::fromString((string) $model->getKey()),
            array_values($lessons),
        );
    }

    private function isContentIdUniqueViolation(QueryException $exception): bool
    {
        $state = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $message = $exception->getMessage();

        return ($state === '23505' && (str_contains($message, 'academic_lessons_pkey') || str_contains($message, 'academic_lesson_blocks_pkey')))
            || ($state === '23000' && (str_contains($message, 'UNIQUE constraint failed: academic_lessons.id') || str_contains($message, 'UNIQUE constraint failed: academic_lesson_blocks.id')));
    }
}
