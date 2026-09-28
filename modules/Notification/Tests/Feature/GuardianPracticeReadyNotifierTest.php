<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Identity\Domain\Entities\GuardianRelationship;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\GuardianRelationshipRepository;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Notification\Application\Services\GuardianPracticeReadyNotifier;
use Modules\Notification\Domain\Repositories\NotificationRepository;

uses(RefreshDatabase::class);

it('avisa una sola vez a la persona acompañante cuando una práctica queda lista', function (): void {
    $guardian = User::register((string) Str::uuid(), 'Persona acompañante', Email::fromString(Str::uuid().'@edudrive.cr'), 'hash');
    $minor = User::register((string) Str::uuid(), 'Estudiante', Email::fromString(Str::uuid().'@edudrive.cr'), 'hash');
    app(UserRepository::class)->save($guardian);
    app(UserRepository::class)->save($minor);
    app(GuardianRelationshipRepository::class)->save(GuardianRelationship::create((string) Str::uuid(), $guardian->id(), $minor->id()));

    $notifier = app(GuardianPracticeReadyNotifier::class);
    $lessonId = (string) Str::uuid();
    $secondLessonId = (string) Str::uuid();
    $notifier->notifyForMinor($minor->id(), $lessonId, 'Cruzar con información');
    $notifier->notifyForMinor($minor->id(), $lessonId, 'Cruzar con información');
    $notifier->notifyForMinor($minor->id(), $secondLessonId, 'Cruzar con información');

    $notifications = app(NotificationRepository::class)->allForUser($guardian->id());
    expect($notifications)->toHaveCount(2)
        ->and($notifications[0]->subject())->toBe('Práctica lista para observar: Cruzar con información')
        ->and($notifications[0]->body())->toContain('Mi acompañamiento')
        ->and($notifications[0]->actionUrl())->toBe('/mi-acompanamiento/'.$minor->id().'?lesson_id='.$lessonId.'#practice-'.$lessonId)
        ->and($notifications[1]->actionUrl())->toBe('/mi-acompanamiento/'.$minor->id().'?lesson_id='.$secondLessonId.'#practice-'.$secondLessonId);
});
