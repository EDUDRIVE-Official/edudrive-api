<?php

declare(strict_types=1);

namespace Modules\Identity\Application\UseCases;

use DateTimeImmutable;
use Modules\Identity\Application\Commands\UpdateTeacherProfileCommand;
use Modules\Identity\Application\Responses\TeacherProfileResponse;
use Modules\Identity\Domain\Entities\TeacherProfile;
use Modules\Identity\Domain\Repositories\TeacherProfileRepository;

final readonly class UpdateTeacherProfileHandler
{
    public function __construct(
        private TeacherProfileRepository $profiles,
    ) {}

    public function handle(UpdateTeacherProfileCommand $command): TeacherProfileResponse
    {
        $now = new DateTimeImmutable;
        $profile = $this->profiles->findByUserId($command->userId)
            ?? TeacherProfile::create(userId: $command->userId, occurredAt: $now);

        $profile->update(
            specialties: $command->specialties,
            certifications: $command->certifications,
            occurredAt: $now,
        );

        $this->profiles->save($profile);

        return TeacherProfileResponse::fromTeacherProfile($profile);
    }
}
