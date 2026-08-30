<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use DateTimeImmutable;
use Modules\Identity\Application\Commands\UpdateStudentProfileCommand;
use Modules\Identity\Application\Responses\StudentProfileResponse;
use Modules\Identity\Domain\Entities\StudentProfile;
use Modules\Identity\Domain\Repositories\StudentProfileRepository;

final readonly class UpdateStudentProfileHandler
{
    public function __construct(
        private StudentProfileRepository $profiles,
    ) {}

    public function handle(UpdateStudentProfileCommand $command): StudentProfileResponse
    {
        $now = new DateTimeImmutable;
        $profile = $this->profiles->findByUserId($command->userId)
            ?? StudentProfile::create(userId: $command->userId, occurredAt: $now);

        $profile->update(
            educationLevel: $command->educationLevel,
            accessibilityNeeds: $command->accessibilityNeeds,
            learningPreferences: $command->learningPreferences,
            occurredAt: $now,
        );

        $this->profiles->save($profile);

        return StudentProfileResponse::fromStudentProfile($profile);
    }
}
