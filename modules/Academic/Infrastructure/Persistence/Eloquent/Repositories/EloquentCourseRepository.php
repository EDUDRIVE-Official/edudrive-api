<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Application\Exceptions\CourseCurriculumIdConflict;
use Modules\Academic\Domain\Aggregates\Course;
use Modules\Academic\Domain\Entities\CourseModule;
use Modules\Academic\Domain\Entities\CourseUnit;
use Modules\Academic\Domain\Enums\CourseModality;
use Modules\Academic\Domain\Enums\CourseStatus;
use Modules\Academic\Domain\Repositories\CourseRepository;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseModuleId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;
use Modules\Academic\Domain\ValueObjects\CurriculumCode;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\CourseModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\CourseModuleModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\CourseUnitModel;

final class EloquentCourseRepository implements CourseRepository
{
    public function save(Course $course): void
    {
        try {
            DB::transaction(function () use ($course): void {
                $model = CourseModel::query()->updateOrCreate(
                    ['id' => $course->id()->value()],
                    [
                        'code' => $course->code()->value(),
                        'title' => $course->title()->value(),
                        'description' => $course->description(),
                        'objectives' => $course->objectives(),
                        'prerequisites' => $course->prerequisites(),
                        'modality' => $course->modality()?->value,
                        'duration_hours' => $course->durationHours(),
                        'status' => $course->status()->value,
                        'published_at' => $course->publishedAt(),
                        'archived_at' => $course->archivedAt(),
                    ],
                );

                $this->assertCurriculumOwnership($course);
                $this->moveExistingCurriculumToTemporaryValues($course->id());
                $this->syncModules($model, $course->modules());
                $this->syncUnits($model, $course->modules());
                $this->deleteObsoleteCurriculum($course);
                $this->applyFinalPositions($course->modules());
                $this->syncPrerequisites($course->modules());
            });
        } catch (QueryException $exception) {
            if ($this->isCurriculumIdUniqueViolation($exception)) {
                throw CourseCurriculumIdConflict::create();
            }

            throw $exception;
        }
    }

    public function findById(CourseId $id): ?Course
    {
        $model = $this->queryWithCurriculum()->find($id->value());

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByCode(CourseCode $code): ?Course
    {
        $model = $this->queryWithCurriculum()
            ->where('code', $code->value())
            ->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function existsByCode(CourseCode $code): bool
    {
        return CourseModel::query()
            ->where('code', $code->value())
            ->exists();
    }

    public function all(): array
    {
        $courses = $this->queryWithCurriculum()
            ->orderBy('created_at')
            ->get()
            ->map(fn (CourseModel $model): Course => $this->toDomain($model))
            ->all();

        return array_values($courses);
    }

    /** @return Builder<CourseModel> */
    private function queryWithCurriculum(): Builder
    {
        return CourseModel::query()->with([
            'modules.prerequisiteModules',
            'modules.units.prerequisiteUnits',
        ]);
    }

    /** @param list<CourseModule> $modules */
    private function syncModules(CourseModel $course, array $modules): void
    {
        foreach ($modules as $index => $module) {
            $attributes = [
                'course_id' => $course->getKey(),
                'code' => $module->code()->value(),
                'title' => $module->title(),
                'description' => $module->description(),
                'objectives' => $module->objectives(),
                'duration_minutes' => $module->durationMinutes(),
                'position' => -1_000_000 - $index,
            ];
            $updated = CourseModuleModel::query()
                ->whereKey($module->id()->value())
                ->where('course_id', $course->getKey())
                ->update($attributes);

            if ($updated === 0) {
                CourseModuleModel::query()->create([
                    'id' => $module->id()->value(),
                    ...$attributes,
                ]);
            }
        }
    }

    /** @param list<CourseModule> $modules */
    private function syncUnits(CourseModel $course, array $modules): void
    {
        $temporaryPosition = -2_000_000;
        foreach ($modules as $module) {
            foreach ($module->units() as $unit) {
                $attributes = [
                    'module_id' => $module->id()->value(),
                    'code' => $unit->code()->value(),
                    'title' => $unit->title(),
                    'description' => $unit->description(),
                    'objectives' => $unit->objectives(),
                    'duration_minutes' => $unit->durationMinutes(),
                    'position' => $temporaryPosition--,
                ];
                $updated = CourseUnitModel::query()
                    ->whereKey($unit->id()->value())
                    ->whereIn(
                        'module_id',
                        CourseModuleModel::query()
                            ->select('id')
                            ->where('course_id', $course->getKey()),
                    )
                    ->update($attributes);

                if ($updated === 0) {
                    CourseUnitModel::query()->create([
                        'id' => $unit->id()->value(),
                        ...$attributes,
                    ]);
                }
            }
        }
    }

    /** @param list<CourseModule> $modules */
    private function syncPrerequisites(array $modules): void
    {
        $moduleIds = $this->moduleIds($modules);
        $unitIds = $this->unitIds($modules);

        if ($moduleIds !== []) {
            DB::table('academic_module_prerequisites')->whereIn('module_id', $moduleIds)->delete();
        }

        if ($unitIds !== []) {
            DB::table('academic_unit_prerequisites')->whereIn('unit_id', $unitIds)->delete();
        }

        $modulePrerequisites = [];
        $unitPrerequisites = [];

        foreach ($modules as $module) {
            foreach ($module->prerequisiteModuleIds() as $prerequisiteId) {
                $modulePrerequisites[] = [
                    'module_id' => $module->id()->value(),
                    'prerequisite_module_id' => $prerequisiteId->value(),
                ];
            }

            foreach ($module->units() as $unit) {
                foreach ($unit->prerequisiteUnitIds() as $prerequisiteId) {
                    $unitPrerequisites[] = [
                        'unit_id' => $unit->id()->value(),
                        'prerequisite_unit_id' => $prerequisiteId->value(),
                    ];
                }
            }
        }

        if ($modulePrerequisites !== []) {
            DB::table('academic_module_prerequisites')->insert($modulePrerequisites);
        }

        if ($unitPrerequisites !== []) {
            DB::table('academic_unit_prerequisites')->insert($unitPrerequisites);
        }
    }

    private function assertCurriculumOwnership(Course $course): void
    {
        $moduleIds = $this->moduleIds($course->modules());
        $unitIds = $this->unitIds($course->modules());
        $courseId = $course->id()->value();

        $foreignModuleExists = $moduleIds !== []
            && CourseModuleModel::query()
                ->whereIn('id', $moduleIds)
                ->where('course_id', '!=', $courseId)
                ->exists();

        $foreignUnitExists = $unitIds !== []
            && DB::table('academic_course_units as units')
                ->join('academic_course_modules as modules', 'modules.id', '=', 'units.module_id')
                ->whereIn('units.id', $unitIds)
                ->where('modules.course_id', '!=', $courseId)
                ->exists();

        if ($foreignModuleExists || $foreignUnitExists) {
            throw CourseCurriculumIdConflict::create();
        }
    }

    private function moveExistingCurriculumToTemporaryValues(CourseId $courseId): void
    {
        $modules = CourseModuleModel::query()
            ->where('course_id', $courseId->value())
            ->orderBy('id')
            ->get();

        foreach ($modules as $index => $module) {
            $module->update([
                'code' => '__TMP__'.$module->getKey(),
                'position' => -1 - $index,
            ]);
        }

        $moduleIds = $modules->modelKeys();

        if ($moduleIds === []) {
            return;
        }

        $units = CourseUnitModel::query()
            ->whereIn('module_id', $moduleIds)
            ->orderBy('id')
            ->get();

        foreach ($units as $index => $unit) {
            $unit->update([
                'code' => '__TMP__'.$unit->getKey(),
                'position' => -1 - $index,
            ]);
        }
    }

    private function deleteObsoleteCurriculum(Course $course): void
    {
        $persistedModuleIds = CourseModuleModel::query()
            ->where('course_id', $course->id()->value())
            ->pluck('id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->all();

        $incomingUnitIds = $this->unitIds($course->modules());

        if ($persistedModuleIds !== []) {
            $obsoleteUnits = CourseUnitModel::query()->whereIn('module_id', $persistedModuleIds);

            if ($incomingUnitIds !== []) {
                $obsoleteUnits->whereNotIn('id', $incomingUnitIds);
            }

            $obsoleteUnits->delete();
        }

        $obsoleteModules = CourseModuleModel::query()->where('course_id', $course->id()->value());
        $incomingModuleIds = $this->moduleIds($course->modules());

        if ($incomingModuleIds !== []) {
            $obsoleteModules->whereNotIn('id', $incomingModuleIds);
        }

        $obsoleteModules->delete();
    }

    /** @param list<CourseModule> $modules */
    private function applyFinalPositions(array $modules): void
    {
        foreach ($modules as $module) {
            CourseModuleModel::query()
                ->whereKey($module->id()->value())
                ->update(['position' => $module->position()]);

            foreach ($module->units() as $unit) {
                CourseUnitModel::query()
                    ->whereKey($unit->id()->value())
                    ->update(['position' => $unit->position()]);
            }
        }
    }

    private function toDomain(CourseModel $model): Course
    {
        $modality = $model->getAttribute('modality');
        $modulePositions = $model->modules
            ->mapWithKeys(static fn (CourseModuleModel $module): array => [
                (string) $module->getKey() => (int) $module->getAttribute('position'),
            ])
            ->all();
        $modules = $model->modules
            ->map(fn (CourseModuleModel $module): CourseModule => $this->moduleToDomain($module, $modulePositions))
            ->all();

        return Course::restore(
            id: CourseId::fromString((string) $model->getAttribute('id')),
            code: CourseCode::fromString((string) $model->getAttribute('code')),
            title: CourseTitle::fromString((string) $model->getAttribute('title')),
            description: $this->nullableString($model->getAttribute('description')),
            objectives: $this->nullableString($model->getAttribute('objectives')),
            prerequisites: $this->nullableString($model->getAttribute('prerequisites')),
            modality: $modality === null ? null : CourseModality::from((string) $modality),
            durationHours: $this->nullableInteger($model->getAttribute('duration_hours')),
            status: CourseStatus::from((string) $model->getAttribute('status')),
            publishedAt: $this->toDateTimeImmutable($model->getAttribute('published_at')),
            archivedAt: $this->toDateTimeImmutable($model->getAttribute('archived_at')),
            modules: array_values($modules),
        );
    }

    /** @param array<string, int> $modulePositions */
    private function moduleToDomain(CourseModuleModel $model, array $modulePositions): CourseModule
    {
        $prerequisiteIds = $model->prerequisiteModules
            ->sortBy('position')
            ->map(static fn (CourseModuleModel $module): CourseModuleId => CourseModuleId::fromString((string) $module->getKey()))
            ->values()
            ->all();
        $units = $model->units
            ->map(fn (CourseUnitModel $unit): CourseUnit => $this->unitToDomain($unit, $modulePositions))
            ->all();

        return CourseModule::create(
            id: CourseModuleId::fromString((string) $model->getKey()),
            code: CurriculumCode::fromString((string) $model->getAttribute('code')),
            title: (string) $model->getAttribute('title'),
            description: (string) $model->getAttribute('description'),
            objectives: $this->nullableString($model->getAttribute('objectives')),
            durationMinutes: $this->nullableInteger($model->getAttribute('duration_minutes')),
            position: (int) $model->getAttribute('position'),
            prerequisiteModuleIds: array_values($prerequisiteIds),
            units: array_values($units),
        );
    }

    /** @param array<string, int> $modulePositions */
    private function unitToDomain(CourseUnitModel $model, array $modulePositions): CourseUnit
    {
        $prerequisiteIds = $model->prerequisiteUnits
            ->sort(static function (CourseUnitModel $left, CourseUnitModel $right) use ($modulePositions): int {
                $leftModuleId = (string) $left->getAttribute('module_id');
                $rightModuleId = (string) $right->getAttribute('module_id');

                return [
                    $modulePositions[$leftModuleId] ?? PHP_INT_MAX,
                    (int) $left->getAttribute('position'),
                    (string) $left->getKey(),
                ] <=> [
                    $modulePositions[$rightModuleId] ?? PHP_INT_MAX,
                    (int) $right->getAttribute('position'),
                    (string) $right->getKey(),
                ];
            })
            ->map(static fn (CourseUnitModel $unit): CourseUnitId => CourseUnitId::fromString((string) $unit->getKey()))
            ->values()
            ->all();

        return CourseUnit::create(
            id: CourseUnitId::fromString((string) $model->getKey()),
            code: CurriculumCode::fromString((string) $model->getAttribute('code')),
            title: (string) $model->getAttribute('title'),
            description: (string) $model->getAttribute('description'),
            objectives: $this->nullableString($model->getAttribute('objectives')),
            durationMinutes: $this->nullableInteger($model->getAttribute('duration_minutes')),
            position: (int) $model->getAttribute('position'),
            prerequisiteUnitIds: array_values($prerequisiteIds),
        );
    }

    /** @param list<CourseModule> $modules
     * @return list<string>
     */
    private function moduleIds(array $modules): array
    {
        return array_map(static fn (CourseModule $module): string => $module->id()->value(), $modules);
    }

    /** @param list<CourseModule> $modules
     * @return list<string>
     */
    private function unitIds(array $modules): array
    {
        $ids = [];

        foreach ($modules as $module) {
            foreach ($module->units() as $unit) {
                $ids[] = $unit->id()->value();
            }
        }

        return $ids;
    }

    private function nullableString(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    private function nullableInteger(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
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

    private function isCurriculumIdUniqueViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $message = $exception->getMessage();

        if ($sqlState === '23505') {
            return str_contains($message, 'academic_course_modules_pkey')
                || str_contains($message, 'academic_course_units_pkey');
        }

        return $sqlState === '23000'
            && (str_contains($message, 'UNIQUE constraint failed: academic_course_modules.id')
                || str_contains($message, 'UNIQUE constraint failed: academic_course_units.id'));
    }
}
