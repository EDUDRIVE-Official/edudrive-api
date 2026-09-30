<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Identity\Domain\Entities\User;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;
use Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Notification\Domain\Aggregates\Notification;
use Modules\Notification\Domain\Enums\NotificationChannel;
use Modules\Notification\Domain\Repositories\NotificationPreferenceRepository;
use Modules\Notification\Domain\Repositories\NotificationRepository;
use Modules\Notification\Domain\ValueObjects\NotificationId;
use Tests\TestCase;

uses(RefreshDatabase::class);

function persistedNotificationWebUser(string $name = 'Usuario de notificaciones'): User
{
    $user = User::register(
        id: (string) Str::uuid(),
        name: $name,
        email: Email::fromString(Str::uuid().'@edudrive.cr'),
        passwordHash: 'hash',
    );
    app(UserRepository::class)->save($user);

    return $user;
}

it('requiere autenticación para consultar la bandeja web', function (): void {
    /** @var TestCase $this */
    $this->get('/mis-notificaciones')->assertRedirect(route('login'));
});

it('muestra las notificaciones propias y permite marcarlas como leídas', function (): void {
    /** @var TestCase $this */
    $user = persistedNotificationWebUser();
    $notification = Notification::send(
        id: NotificationId::fromString((string) Str::uuid()),
        userId: $user->id(),
        channel: NotificationChannel::Web,
        category: 'logro',
        subject: 'Nuevo logro',
        body: 'Completaste tu primer curso.',
    );
    app(NotificationRepository::class)->save($notification);
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->get('/mis-notificaciones')
        ->assertOk()
        ->assertSeeText('Nuevo logro')
        ->assertSeeText('1 sin leer')
        ->assertSee('aria-label="1 notificación sin leer"', false);
    $this->post('/mis-notificaciones/'.$notification->id()->value().'/leer')->assertRedirect()->assertSessionHas('status');

    expect(app(NotificationRepository::class)->findById($notification->id())?->status()->value)->toBe('read');
    $this->get('/mis-notificaciones')
        ->assertOk()
        ->assertDontSee('aria-label="1 notificación sin leer"', false);
});

it('muestra el aviso de acompañamiento con acceso al pasaporte vial', function (): void {
    /** @var TestCase $this */
    $user = persistedNotificationWebUser('Estudiante acompañado');
    app(NotificationRepository::class)->save(Notification::send(
        id: NotificationId::fromString((string) Str::uuid()),
        userId: $user->id(),
        channel: NotificationChannel::Web,
        category: 'acompañamiento',
        subject: 'Nueva práctica acompañada: Cruce seguro',
        body: 'La evidencia ya forma parte de tu Pasaporte Vial.',
    ));
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->get('/mis-notificaciones')
        ->assertOk()
        ->assertSeeText('Acompañamiento')
        ->assertSeeText('Ver en mi Pasaporte Vial')
        ->assertSee(route('road-passport.show'));
});

it('muestra el aviso de reflexión con acceso al acompañamiento', function (): void {
    /** @var TestCase $this */
    $user = persistedNotificationWebUser('Persona tutora notificada');
    app(NotificationRepository::class)->save(Notification::send(
        id: NotificationId::fromString((string) Str::uuid()),
        userId: $user->id(),
        channel: NotificationChannel::Web,
        category: 'reflexion_acompañamiento',
        subject: 'Estudiante respondió sobre Cruce seguro',
        body: 'Ya podés leer su reflexión.',
    ));
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->get('/mis-notificaciones')
        ->assertOk()
        ->assertSeeText('Reflexión del estudiante')
        ->assertSeeText('Ver en Mi acompañamiento')
        ->assertSee(route('guardians.web.index'));
});

it('abre el destino interno de una práctica pendiente', function (): void {
    /** @var TestCase $this */
    $user = persistedNotificationWebUser('Persona tutora con práctica');
    $minorId = (string) Str::uuid();
    $actionUrl = '/mi-acompanamiento/'.$minorId.'#pending-practice-form';
    $notificationId = NotificationId::fromString((string) Str::uuid());
    app(NotificationRepository::class)->save(Notification::send(
        id: $notificationId,
        userId: $user->id(),
        channel: NotificationChannel::Web,
        category: 'practica_lista',
        subject: 'Práctica lista para observar',
        body: 'El estudiante completó la parte digital.',
        actionUrl: $actionUrl,
    ));
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->get('/mis-notificaciones')
        ->assertOk()
        ->assertSeeText('Práctica familiar')
        ->assertSeeText('Abrir actividad')
        ->assertSee('href="'.route('notifications.open', $notificationId->value()).'"', false);
    $this->get(route('notifications.open', $notificationId->value()))
        ->assertRedirect($actionUrl);

    expect(app(NotificationRepository::class)->findById($notificationId)?->status()->value)->toBe('read');

    $otherUser = persistedNotificationWebUser('Persona sin acceso al aviso');
    $this->actingAs(UserModel::query()->findOrFail($otherUser->id()), 'web');
    $this->get(route('notifications.open', $notificationId->value()))->assertNotFound();
});

it('actualiza preferencias y consentimiento desde la pantalla web', function (): void {
    /** @var TestCase $this */
    $user = persistedNotificationWebUser();
    $this->actingAs(UserModel::query()->findOrFail($user->id()), 'web');

    $this->put('/mis-notificaciones/preferencias', [
        'allowed_channels' => ['web'],
        'frequency' => 'daily',
        'quiet_hours_start' => '22:00',
        'quiet_hours_end' => '07:00',
    ])->assertRedirect()->assertSessionHas('status');

    $preference = app(NotificationPreferenceRepository::class)->findByUserId($user->id());
    expect($preference?->frequency()->value)->toBe('daily')
        ->and($preference?->mutedCategories())->toBe([]);

    $this->delete('/mis-notificaciones/consentimiento')->assertRedirect()->assertSessionHas('status');
    expect(app(NotificationPreferenceRepository::class)->findByUserId($user->id())?->consentGiven())->toBeFalse();
});

it('protege el envío administrativo y permite enviar con el permiso correspondiente', function (): void {
    /** @var TestCase $this */
    $recipient = persistedNotificationWebUser('Destinatario');
    $ordinaryUser = persistedNotificationWebUser('Sin permisos');
    $this->actingAs(UserModel::query()->findOrFail($ordinaryUser->id()), 'web');
    $this->get('/admin/notificaciones')->assertForbidden();

    $this->actingAs(actingAsSuperAdminUser(), 'web');
    $this->get('/admin/notificaciones')
        ->assertOk()
        ->assertSeeText('Destinatario')
        ->assertSeeText('Propósito del aviso')
        ->assertSeeText('Cursos y aprendizaje')
        ->assertSeeText('En EDUDRIVE');
    $this->post('/admin/notificaciones', [
        'user_id' => $recipient->id(),
        'channel' => 'web',
        'category' => 'curso',
        'subject' => 'Curso disponible',
        'body' => 'Ya podés iniciar el curso.',
    ])->assertRedirect()->assertSessionHas('status');

    expect(app(NotificationRepository::class)->allForUser($recipient->id()))->toHaveCount(1);
});
