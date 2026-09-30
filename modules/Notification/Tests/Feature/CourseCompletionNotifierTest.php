<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Notification\Application\Services\CourseCompletionNotifier;
use Modules\Notification\Domain\Repositories\NotificationRepository;

uses(RefreshDatabase::class);

it('crea una sola notificacion web por mision completada', function (): void {
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Estudiante notificado',
        email: Email::fromString(Str::uuid().'@edudrive.cr'),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    $notifier = app(CourseCompletionNotifier::class);
    $courseId = (string) Str::uuid();
    $notifier->notify($user->id(), $courseId, 'Misión Camino Seguro');
    $notifier->notify($user->id(), $courseId, 'Misión Camino Seguro');

    $notifications = app(NotificationRepository::class)->allForUser($user->id());

    expect($notifications)->toHaveCount(1)
        ->and($notifications[0]->channel()->value)->toBe('web')
        ->and($notifications[0]->category())->toBe('curso')
        ->and($notifications[0]->subject())->toContain('Misión Camino Seguro')
        ->and($notifications[0]->body())->toContain('100 XP');
});
