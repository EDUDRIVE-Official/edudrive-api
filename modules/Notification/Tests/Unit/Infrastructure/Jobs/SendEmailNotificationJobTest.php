<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Notification\Infrastructure\Jobs\SendEmailNotificationJob;
use Modules\Notification\Infrastructure\Mail\NotificationMail;

uses(RefreshDatabase::class);

it('envia el correo de notificacion al usuario existente', function (): void {
    Mail::fake();
    $user = User::register(
        id: (string) Str::uuid(),
        name: 'Usuario de correo',
        email: Email::fromString(sprintf('%s@edudrive.cr', Str::uuid())),
        passwordHash: 'hashed-password',
    );
    app(UserRepository::class)->save($user);

    (new SendEmailNotificationJob($user->id(), 'Asunto de prueba', 'Cuerpo de prueba'))
        ->handle(app(UserRepository::class));

    Mail::assertSent(NotificationMail::class, fn (NotificationMail $mail): bool => $mail->hasTo($user->email()->value())
        && $mail->notificationSubject === 'Asunto de prueba'
        && $mail->notificationBody === 'Cuerpo de prueba');
});

it('no envia ningun correo cuando el usuario no existe', function (): void {
    Mail::fake();

    (new SendEmailNotificationJob((string) Str::uuid(), 'Asunto', 'Cuerpo'))
        ->handle(app(UserRepository::class));

    Mail::assertNothingSent();
});
