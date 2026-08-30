<?php

declare(strict_types=1);

use Modules\Identity\Application\Commands\UpdateStudentProfileCommand;
use Modules\Identity\Application\UseCases\UpdateStudentProfileHandler;
use Modules\Identity\Domain\Entities\StudentProfile;
use Modules\Identity\Domain\Repositories\StudentProfileRepository;

final class InMemoryStudentProfileRepositoryForUpdate implements StudentProfileRepository
{
    /** @var array<string, StudentProfile> */
    public array $profiles = [];

    public function save(StudentProfile $profile): void
    {
        $this->profiles[$profile->userId()] = $profile;
    }

    public function findByUserId(string $userId): ?StudentProfile
    {
        return $this->profiles[$userId] ?? null;
    }
}

it('crea el perfil la primera vez que se actualiza', function (): void {
    $profiles = new InMemoryStudentProfileRepositoryForUpdate;
    $handler = new UpdateStudentProfileHandler($profiles);

    $response = $handler->handle(new UpdateStudentProfileCommand(
        userId: 'user-1',
        educationLevel: 'Universitario incompleto',
        accessibilityNeeds: null,
        learningPreferences: 'Contenido en video',
    ));

    expect($response->educationLevel)->toBe('Universitario incompleto')
        ->and($response->learningPreferences)->toBe('Contenido en video')
        ->and($profiles->profiles)->toHaveCount(1);
});

it('actualiza el perfil existente en vez de duplicarlo', function (): void {
    $profiles = new InMemoryStudentProfileRepositoryForUpdate;
    $handler = new UpdateStudentProfileHandler($profiles);

    $handler->handle(new UpdateStudentProfileCommand(userId: 'user-1', educationLevel: 'Secundaria', accessibilityNeeds: null, learningPreferences: null));
    $response = $handler->handle(new UpdateStudentProfileCommand(userId: 'user-1', educationLevel: 'Universitario', accessibilityNeeds: null, learningPreferences: null));

    expect($response->educationLevel)->toBe('Universitario')
        ->and($profiles->profiles)->toHaveCount(1);
});
