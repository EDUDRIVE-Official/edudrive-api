<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Responses;

final readonly class MyStudentProfileResponse
{
    /**
     * @param  array{status: string, level: int, issued_at: string}|null  $roadPassport
     * @param  list<array{course_id: string, status: string, enrolled_at: string}>  $enrollments
     */
    public function __construct(
        public string $userId,
        public string $name,
        public ?string $dateOfBirth,
        public bool $isMinor,
        public ?string $educationLevel,
        public ?string $accessibilityNeeds,
        public ?string $learningPreferences,
        public ?array $roadPassport,
        public array $enrollments,
    ) {}

    /**
     * @return array{
     *     user_id: string,
     *     name: string,
     *     date_of_birth: string|null,
     *     is_minor: bool,
     *     education_level: string|null,
     *     accessibility_needs: string|null,
     *     learning_preferences: string|null,
     *     road_passport: array{status: string, level: int, issued_at: string}|null,
     *     enrollments: list<array{course_id: string, status: string, enrolled_at: string}>
     * }
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'name' => $this->name,
            'date_of_birth' => $this->dateOfBirth,
            'is_minor' => $this->isMinor,
            'education_level' => $this->educationLevel,
            'accessibility_needs' => $this->accessibilityNeeds,
            'learning_preferences' => $this->learningPreferences,
            'road_passport' => $this->roadPassport,
            'enrollments' => $this->enrollments,
        ];
    }
}
