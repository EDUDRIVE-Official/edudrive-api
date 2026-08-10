<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Aggregates;

use DateTimeImmutable;
use Modules\Academic\Domain\Entities\CourseModule;
use Modules\Academic\Domain\Enums\CourseModality;
use Modules\Academic\Domain\Enums\CourseStatus;
use Modules\Academic\Domain\Exceptions\ArchivedCourseCannotBeModified;
use Modules\Academic\Domain\Exceptions\CourseAlreadyArchived;
use Modules\Academic\Domain\Exceptions\CourseAlreadyPublished;
use Modules\Academic\Domain\Exceptions\CourseCannotBeReopened;
use Modules\Academic\Domain\Exceptions\CourseContentCannotBeModified;
use Modules\Academic\Domain\Exceptions\CourseCurriculumCannotBeModified;
use Modules\Academic\Domain\Exceptions\CourseCurriculumRequired;
use Modules\Academic\Domain\Exceptions\CourseModuleRequiresUnits;
use Modules\Academic\Domain\Exceptions\CourseReviewStateInvalid;
use Modules\Academic\Domain\Exceptions\CourseUnitContentRequired;
use Modules\Academic\Domain\Exceptions\DuplicateCourseModule;
use Modules\Academic\Domain\Exceptions\DuplicateCourseUnit;
use Modules\Academic\Domain\Exceptions\InvalidCurriculumPosition;
use Modules\Academic\Domain\Exceptions\InvalidCurriculumPrerequisite;
use Modules\Academic\Domain\ValueObjects\CourseCode;
use Modules\Academic\Domain\ValueObjects\CourseId;
use Modules\Academic\Domain\ValueObjects\CourseTitle;
use Modules\Academic\Domain\ValueObjects\CourseUnitId;
use Modules\Academic\Domain\ValueObjects\UnitContentCoverage;

final class Course
{
    private function __construct(
        private readonly CourseId $id,
        private readonly CourseCode $code,
        private CourseTitle $title,
        private ?string $description,
        private ?string $objectives,
        private ?string $prerequisites,
        private ?CourseModality $modality,
        private ?int $durationHours,
        private CourseStatus $status,
        private ?DateTimeImmutable $publishedAt,
        private ?DateTimeImmutable $archivedAt,
        /** @var list<CourseModule> */
        private array $modules,
    ) {}

    public static function create(
        CourseId $id,
        CourseCode $code,
        CourseTitle $title,
        ?string $description = null,
        ?string $objectives = null,
        ?string $prerequisites = null,
        ?CourseModality $modality = null,
        ?int $durationHours = null,
    ): self {
        return new self(
            id: $id,
            code: $code,
            title: $title,
            description: self::normalizeText($description),
            objectives: self::normalizeText($objectives),
            prerequisites: self::normalizeText($prerequisites),
            modality: $modality,
            durationHours: $durationHours,
            status: CourseStatus::Draft,
            publishedAt: null,
            archivedAt: null,
            modules: [],
        );
    }

    /** @param list<CourseModule> $modules */
    public static function restore(
        CourseId $id,
        CourseCode $code,
        CourseTitle $title,
        ?string $description,
        ?string $objectives,
        ?string $prerequisites,
        ?CourseModality $modality,
        ?int $durationHours,
        CourseStatus $status,
        ?DateTimeImmutable $publishedAt,
        ?DateTimeImmutable $archivedAt,
        array $modules = [],
    ): self {
        $course = new self(
            id: $id,
            code: $code,
            title: $title,
            description: self::normalizeText($description),
            objectives: self::normalizeText($objectives),
            prerequisites: self::normalizeText($prerequisites),
            modality: $modality,
            durationHours: $durationHours,
            status: $status,
            publishedAt: $publishedAt,
            archivedAt: $archivedAt,
            modules: $modules,
        );

        $course->validateCurriculum($modules);

        if ($status->isPublished() && $modules !== []) {
            $course->ensureEveryModuleHasUnits();
        }

        return $course;
    }

    public function rename(CourseTitle $title): void
    {
        $this->ensureIsNotArchived();

        $this->title = $title;
    }

    public function changeDescription(?string $description): void
    {
        $this->ensureIsNotArchived();

        $this->description = self::normalizeText($description);
    }

    /** @param list<CourseModule> $modules */
    public function replaceCurriculum(array $modules): void
    {
        if (! $this->status->isDraft()) {
            throw CourseCurriculumCannotBeModified::create();
        }

        $this->validateCurriculum($modules);

        $this->modules = $modules;
    }

    public function submitForReview(): void
    {
        if (! $this->status->isDraft()) {
            throw CourseReviewStateInvalid::create();
        }

        $this->status = CourseStatus::UnderReview;
    }

    public function approve(): void
    {
        if (! $this->status->isUnderReview()) {
            throw CourseReviewStateInvalid::create();
        }

        $this->status = CourseStatus::Approved;
    }

    public function sendBackToDraft(): void
    {
        if (! $this->status->isUnderReview() && ! $this->status->isApproved()) {
            throw CourseReviewStateInvalid::create();
        }

        $this->status = CourseStatus::Draft;
    }

    public function reopen(): void
    {
        if (! $this->status->isPublished()) {
            throw CourseCannotBeReopened::create();
        }

        $this->status = CourseStatus::Draft;
        $this->publishedAt = null;
    }

    public function publish(DateTimeImmutable $publishedAt, UnitContentCoverage $coverage): void
    {
        $this->ensureIsNotArchived();

        if ($this->status->isPublished()) {
            throw CourseAlreadyPublished::create();
        }

        if (! $this->status->isApproved()) {
            throw CourseReviewStateInvalid::create();
        }

        if ($this->modules === []) {
            throw CourseCurriculumRequired::create();
        }

        $this->ensureEveryModuleHasUnits();

        foreach ($this->modules as $module) {
            foreach ($module->units() as $unit) {
                if (! $coverage->covers($unit->id())) {
                    throw CourseUnitContentRequired::create();
                }
            }
        }

        $this->status = CourseStatus::Published;
        $this->publishedAt = $publishedAt;
    }

    public function archive(DateTimeImmutable $archivedAt): void
    {
        if ($this->status->isArchived()) {
            throw CourseAlreadyArchived::create();
        }

        $this->status = CourseStatus::Archived;
        $this->archivedAt = $archivedAt;
    }

    public function id(): CourseId
    {
        return $this->id;
    }

    public function code(): CourseCode
    {
        return $this->code;
    }

    public function title(): CourseTitle
    {
        return $this->title;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function objectives(): ?string
    {
        return $this->objectives;
    }

    public function prerequisites(): ?string
    {
        return $this->prerequisites;
    }

    public function modality(): ?CourseModality
    {
        return $this->modality;
    }

    public function durationHours(): ?int
    {
        return $this->durationHours;
    }

    public function status(): CourseStatus
    {
        return $this->status;
    }

    public function publishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function archivedAt(): ?DateTimeImmutable
    {
        return $this->archivedAt;
    }

    /** @return list<CourseModule> */
    public function modules(): array
    {
        return $this->modules;
    }

    public function ownsUnit(CourseUnitId $unitId): bool
    {
        foreach ($this->modules as $module) {
            foreach ($module->units() as $unit) {
                if ($unit->id()->equals($unitId)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function ensureContentCanBeModified(): void
    {
        if (! $this->status->isDraft()) {
            throw CourseContentCannotBeModified::create();
        }
    }

    /** @param list<CourseModule> $modules */
    private function validateCurriculum(array $modules): void
    {
        /** @var array<string, true> $moduleIds */
        $moduleIds = [];
        /** @var array<string, true> $moduleCodes */
        $moduleCodes = [];
        /** @var array<string, true> $unitIds */
        $unitIds = [];

        foreach ($modules as $moduleIndex => $module) {
            if ($module->position() !== $moduleIndex + 1) {
                throw InvalidCurriculumPosition::create();
            }

            $moduleId = $module->id()->value();
            $moduleCode = $module->code()->value();

            if (isset($moduleIds[$moduleId]) || isset($moduleCodes[$moduleCode])) {
                throw DuplicateCourseModule::create();
            }

            $prerequisiteModuleIds = [];

            foreach ($module->prerequisiteModuleIds() as $prerequisiteModuleId) {
                $prerequisiteId = $prerequisiteModuleId->value();

                if (isset($prerequisiteModuleIds[$prerequisiteId]) || ! isset($moduleIds[$prerequisiteId])) {
                    throw InvalidCurriculumPrerequisite::create();
                }

                $prerequisiteModuleIds[$prerequisiteId] = true;
            }

            /** @var array<string, true> $unitCodes */
            $unitCodes = [];

            foreach ($module->units() as $unitIndex => $unit) {
                if ($unit->position() !== $unitIndex + 1) {
                    throw InvalidCurriculumPosition::create();
                }

                $unitId = $unit->id()->value();
                $unitCode = $unit->code()->value();

                if (isset($unitIds[$unitId]) || isset($unitCodes[$unitCode])) {
                    throw DuplicateCourseUnit::create();
                }

                $prerequisiteUnitIds = [];

                foreach ($unit->prerequisiteUnitIds() as $prerequisiteUnitId) {
                    $prerequisiteId = $prerequisiteUnitId->value();

                    if (isset($prerequisiteUnitIds[$prerequisiteId]) || ! isset($unitIds[$prerequisiteId])) {
                        throw InvalidCurriculumPrerequisite::create();
                    }

                    $prerequisiteUnitIds[$prerequisiteId] = true;
                }

                $unitIds[$unitId] = true;
                $unitCodes[$unitCode] = true;
            }

            $moduleIds[$moduleId] = true;
            $moduleCodes[$moduleCode] = true;
        }
    }

    private function ensureEveryModuleHasUnits(): void
    {
        foreach ($this->modules as $module) {
            if ($module->units() === []) {
                throw CourseModuleRequiresUnits::create();
            }
        }
    }

    private function ensureIsNotArchived(): void
    {
        if ($this->status->isArchived()) {
            throw ArchivedCourseCannotBeModified::create();
        }
    }

    private static function normalizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
