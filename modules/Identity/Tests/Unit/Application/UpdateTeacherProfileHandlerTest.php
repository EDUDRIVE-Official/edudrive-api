<?php

declare(strict_types=1);

use Modules\Identity\Application\Commands\UpdateTeacherProfileCommand;
use Modules\Identity\Application\UseCases\UpdateTeacherProfileHandler;
use Modules\Identity\Domain\Entities\TeacherProfile;
use Modules\Identity\Domain\Repositories\TeacherProfileRepository;

final class InMemoryTeacherProfileRepositoryForUpdate implements TeacherProfileRepository
{
    /** @var array<string, TeacherProfile> */
    public array $profiles = [];

    public function save(TeacherProfile $profile): void
    {
        $this->profiles[$profile->userId()] = $profile;
    }

    public function findByUserId(string $userId): ?TeacherProfile
    {
        return $this->profiles[$userId] ?? null;
    }
}

it('crea el perfil la primera vez que se actualiza', function (): void {
    $profiles = new InMemoryTeacherProfileRepositoryForUpdate;
    $handler = new UpdateTeacherProfileHandler($profiles);

    $response = $handler->handle(new UpdateTeacherProfileCommand(
        userId: 'user-1',
        specialties: 'Manejo defensivo',
        certifications: 'Instructor certificado INA',
    ));

    expect($response->specialties)->toBe('Manejo defensivo')
        ->and($response->certifications)->toBe('Instructor certificado INA')
        ->and($profiles->profiles)->toHaveCount(1);
});

it('actualiza el perfil existente en vez de duplicarlo', function (): void {
    $profiles = new InMemoryTeacherProfileRepositoryForUpdate;
    $handler = new UpdateTeacherProfileHandler($profiles);

    $handler->handle(new UpdateTeacherProfileCommand(userId: 'user-1', specialties: 'Manejo defensivo', certifications: null));
    $response = $handler->handle(new UpdateTeacherProfileCommand(userId: 'user-1', specialties: 'Motocicletas', certifications: null));

    expect($response->specialties)->toBe('Motocicletas')
        ->and($profiles->profiles)->toHaveCount(1);
});
