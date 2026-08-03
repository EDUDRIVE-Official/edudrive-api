<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Responses;

use Modules\Academic\Domain\Aggregates\EducationalProgram;
use Modules\Academic\Domain\Entities\ProgramCourse;
use Modules\Academic\Domain\Enums\LicenseStage;
use Modules\Academic\Domain\Enums\ProgramContext;
use Modules\Academic\Domain\Enums\VehicleType;

final readonly class ProgramResponse
{
    /**
     * @param  array{
     *     min_age: ?int,
     *     max_age: ?int,
     *     license_stages: list<string>,
     *     contexts: list<string>,
     *     vehicle_types: list<string>
     * }  $audience
     * @param  list<array{course_id: string, position: int}>  $courses
     */
    private function __construct(
        public string $id,
        public string $code,
        public string $title,
        public string $description,
        public string $status,
        public array $audience,
        public array $courses,
        public ?string $publishedAt,
        public ?string $archivedAt,
    ) {}

    public static function fromProgram(EducationalProgram $program): self
    {
        $audience = $program->audience();

        return new self(
            id: $program->id()->value(),
            code: $program->code()->value(),
            title: $program->title(),
            description: $program->description(),
            status: $program->status()->value,
            audience: [
                'min_age' => $audience->minAge(),
                'max_age' => $audience->maxAge(),
                'license_stages' => array_map(static fn (LicenseStage $stage): string => $stage->value, $audience->licenseStages()),
                'contexts' => array_map(static fn (ProgramContext $context): string => $context->value, $audience->contexts()),
                'vehicle_types' => array_map(static fn (VehicleType $vehicleType): string => $vehicleType->value, $audience->vehicleTypes()),
            ],
            courses: array_map(static fn (ProgramCourse $course): array => [
                'course_id' => $course->courseId()->value(),
                'position' => $course->position(),
            ], $program->courses()),
            publishedAt: $program->publishedAt()?->format(DATE_ATOM),
            archivedAt: $program->archivedAt()?->format(DATE_ATOM),
        );
    }

    /**
     * @return array{
     *     id: string,
     *     code: string,
     *     title: string,
     *     description: string,
     *     status: string,
     *     audience: array{
     *         min_age: ?int,
     *         max_age: ?int,
     *         license_stages: list<string>,
     *         contexts: list<string>,
     *         vehicle_types: list<string>
     *     },
     *     courses: list<array{course_id: string, position: int}>,
     *     published_at: ?string,
     *     archived_at: ?string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'audience' => $this->audience,
            'courses' => $this->courses,
            'published_at' => $this->publishedAt,
            'archived_at' => $this->archivedAt,
        ];
    }
}
