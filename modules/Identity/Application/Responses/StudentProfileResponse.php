<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Responses;

use Modules\Identity\Domain\Entities\StudentProfile;

final readonly class StudentProfileResponse
{
    private function __construct(
        public ?string $educationLevel,
        public ?string $accessibilityNeeds,
        public ?string $learningPreferences,
        public string $updatedAt,
        public ?string $learningPurpose = null,
    ) {}

    public static function fromStudentProfile(StudentProfile $profile): self
    {
        return new self(
            $profile->educationLevel(),
            $profile->accessibilityNeeds(),
            $profile->learningPreferences(),
            $profile->updatedAt()->format(DATE_ATOM),
            $profile->learningPurpose(),
        );
    }

    /**
     * @return array{
     *     education_level: string|null,
     *     accessibility_needs: string|null,
     *     learning_preferences: string|null,
     *     learning_purpose: string|null,
     *     updated_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'education_level' => $this->educationLevel,
            'accessibility_needs' => $this->accessibilityNeeds,
            'learning_preferences' => $this->learningPreferences,
            'learning_purpose' => $this->learningPurpose,
            'updated_at' => $this->updatedAt,
        ];
    }
}
