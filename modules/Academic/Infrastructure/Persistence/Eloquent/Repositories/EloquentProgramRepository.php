<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academic\Application\Exceptions\ProgramCodeAlreadyExists;
use Modules\Academic\Domain\Aggregates\EducationalProgram;
use Modules\Academic\Domain\Entities\ProgramCourse;
use Modules\Academic\Domain\Enums\LicenseStage;
use Modules\Academic\Domain\Enums\ProgramContext;
use Modules\Academic\Domain\Enums\ProgramStatus;
use Modules\Academic\Domain\Enums\VehicleType;
use Modules\Academic\Domain\Repositories\ProgramRepository;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\ProgramAudience;
use Modules\Academic\Domain\ValueObjects\ProgramCode;
use Modules\Academic\Domain\ValueObjects\ProgramId;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\ProgramContextModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\ProgramCourseModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\ProgramLicenseStageModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\ProgramModel;
use Modules\Academic\Infrastructure\Persistence\Eloquent\Models\ProgramVehicleTypeModel;

final class EloquentProgramRepository implements ProgramRepository
{
    public function save(EducationalProgram $program): void
    {
        try {
            DB::transaction(function () use ($program): void {
                $model = ProgramModel::query()->updateOrCreate(
                    ['id' => $program->id()->value()],
                    [
                        'code' => $program->code()->value(),
                        'title' => $program->title(),
                        'description' => $program->description(),
                        'min_age' => $program->audience()->minAge(),
                        'max_age' => $program->audience()->maxAge(),
                        'status' => $program->status()->value,
                        'published_at' => $program->publishedAt(),
                        'archived_at' => $program->archivedAt(),
                    ],
                );

                $model->courses()->delete();
                $model->licenseStages()->delete();
                $model->contexts()->delete();
                $model->vehicleTypes()->delete();

                foreach ($program->courses() as $course) {
                    ProgramCourseModel::query()->create([
                        'id' => (string) Str::uuid(),
                        'program_id' => $model->getKey(),
                        'course_id' => $course->courseId()->value(),
                        'position' => $course->position(),
                    ]);
                }

                foreach ($program->audience()->licenseStages() as $index => $stage) {
                    ProgramLicenseStageModel::query()->create([
                        'id' => (string) Str::uuid(),
                        'program_id' => $model->getKey(),
                        'value' => $stage->value,
                        'position' => $index + 1,
                    ]);
                }

                foreach ($program->audience()->contexts() as $index => $context) {
                    ProgramContextModel::query()->create([
                        'id' => (string) Str::uuid(),
                        'program_id' => $model->getKey(),
                        'value' => $context->value,
                        'position' => $index + 1,
                    ]);
                }

                foreach ($program->audience()->vehicleTypes() as $index => $vehicleType) {
                    ProgramVehicleTypeModel::query()->create([
                        'id' => (string) Str::uuid(),
                        'program_id' => $model->getKey(),
                        'value' => $vehicleType->value,
                        'position' => $index + 1,
                    ]);
                }
            });
        } catch (QueryException $exception) {
            if ($this->isProgramCodeUniqueViolation($exception)) {
                throw ProgramCodeAlreadyExists::forCode($program->code());
            }

            throw $exception;
        }
    }

    public function findById(ProgramId $id): ?EducationalProgram
    {
        $model = $this->queryWithChildren()->find($id->value());

        return $model === null ? null : $this->toDomain($model);
    }

    public function findByCode(ProgramCode $code): ?EducationalProgram
    {
        $model = $this->queryWithChildren()->where('code', $code->value())->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function existsByCode(ProgramCode $code): bool
    {
        return ProgramModel::query()->where('code', $code->value())->exists();
    }

    public function all(): array
    {
        $programs = $this->queryWithChildren()
            ->orderBy('code')
            ->get()
            ->map(fn (ProgramModel $model): EducationalProgram => $this->toDomain($model))
            ->all();

        return array_values($programs);
    }

    /** @return Builder<ProgramModel> */
    private function queryWithChildren(): Builder
    {
        return ProgramModel::query()->with([
            'courses',
            'licenseStages',
            'contexts',
            'vehicleTypes',
        ]);
    }

    private function toDomain(ProgramModel $model): EducationalProgram
    {
        $courses = $model->courses
            ->map(static fn (ProgramCourseModel $course): ProgramCourse => ProgramCourse::create(
                CourseId::fromString((string) $course->getAttribute('course_id')),
                (int) $course->getAttribute('position'),
            ))
            ->all();

        $licenseStages = $model->licenseStages
            ->map(static fn (ProgramLicenseStageModel $stage): LicenseStage => LicenseStage::from(
                (string) $stage->getAttribute('value'),
            ))
            ->all();

        $contexts = $model->contexts
            ->map(static fn (ProgramContextModel $context): ProgramContext => ProgramContext::from(
                (string) $context->getAttribute('value'),
            ))
            ->all();

        $vehicleTypes = $model->vehicleTypes
            ->map(static fn (ProgramVehicleTypeModel $vehicleType): VehicleType => VehicleType::from(
                (string) $vehicleType->getAttribute('value'),
            ))
            ->all();

        return EducationalProgram::restore(
            id: ProgramId::fromString((string) $model->getAttribute('id')),
            code: ProgramCode::fromString((string) $model->getAttribute('code')),
            title: (string) $model->getAttribute('title'),
            description: (string) $model->getAttribute('description'),
            audience: ProgramAudience::fromValues(
                minAge: $this->nullableInteger($model->getAttribute('min_age')),
                maxAge: $this->nullableInteger($model->getAttribute('max_age')),
                licenseStages: array_values($licenseStages),
                contexts: array_values($contexts),
                vehicleTypes: array_values($vehicleTypes),
            ),
            courses: array_values($courses),
            status: ProgramStatus::from((string) $model->getAttribute('status')),
            publishedAt: $this->toDateTimeImmutable($model->getAttribute('published_at')),
            archivedAt: $this->toDateTimeImmutable($model->getAttribute('archived_at')),
        );
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

    private function isProgramCodeUniqueViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $message = $exception->getMessage();

        if ($sqlState === '23505') {
            return str_contains($message, 'academic_programs_code_unique');
        }

        return $sqlState === '23000'
            && str_contains($message, 'UNIQUE constraint failed: academic_programs.code');
    }
}
