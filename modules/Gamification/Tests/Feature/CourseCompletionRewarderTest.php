<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Gamification\Application\Services\CourseCompletionRewarder;
use Modules\Gamification\Domain\Repositories\BadgeRepository;
use Modules\Gamification\Domain\Repositories\ExperienceEntryRepository;
use Modules\Gamification\Domain\Repositories\UserBadgeRepository;
use Modules\Gamification\Domain\ValueObjects\BadgeCode;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

uses(RefreshDatabase::class);

it('otorga xp e insignia una sola vez por curso completado', function (): void {
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante recompensado',
        email: Email::fromString(Str::uuid().'@edudrive.cr'),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    $courseId = (string) Str::uuid();
    $competencyId = (string) Str::uuid();
    $rewarder = app(CourseCompletionRewarder::class);

    $rewarder->reward($user->id(), $courseId, $competencyId);
    $rewarder->reward($user->id(), $courseId, $competencyId);

    $experience = app(ExperienceEntryRepository::class)->allForUser($user->id());
    $badge = app(BadgeRepository::class)->findByCode(BadgeCode::fromString('PRIMERA-MISION'));

    expect($experience)->toHaveCount(1)
        ->and($experience[0]->points())->toBe(100)
        ->and($experience[0]->competencyId())->toBe($competencyId)
        ->and($badge)->not->toBeNull()
        ->and(app(UserBadgeRepository::class)->allForUser($user->id()))->toHaveCount(1);
});

it('otorga una insignia temática propia por cada curso real completado', function (): void {
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante con misión completa',
        email: Email::fromString(Str::uuid().'@edudrive.cr'),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);
    $course = createDraftCourseForPublishing('RUTA-SEGURA-01');
    $rewarder = app(CourseCompletionRewarder::class);

    $rewarder->reward($user->id(), $course->id()->value(), null);
    $rewarder->reward($user->id(), $course->id()->value(), null);

    $thematic = app(BadgeRepository::class)->findByCode(BadgeCode::fromString('CURSO-RUTA-SEGURA-01'));

    expect($thematic)->not->toBeNull()
        ->and($thematic?->name())->toBe('Misión completada: Curso de prueba')
        ->and(app(UserBadgeRepository::class)->allForUser($user->id()))->toHaveCount(2);
});
