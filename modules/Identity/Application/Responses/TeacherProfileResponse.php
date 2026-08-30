<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Responses;

use Modules\Identity\Domain\Entities\TeacherProfile;

final readonly class TeacherProfileResponse
{
    private function __construct(
        public ?string $specialties,
        public ?string $certifications,
        public string $updatedAt,
    ) {}

    public static function fromTeacherProfile(TeacherProfile $profile): self
    {
        return new self(
            $profile->specialties(),
            $profile->certifications(),
            $profile->updatedAt()->format(DATE_ATOM),
        );
    }

    /**
     * @return array{
     *     specialties: string|null,
     *     certifications: string|null,
     *     updated_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'specialties' => $this->specialties,
            'certifications' => $this->certifications,
            'updated_at' => $this->updatedAt,
        ];
    }
}
