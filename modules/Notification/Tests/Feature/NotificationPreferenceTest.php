<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Authorization\Domain\Enums\Role;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('el usuario consulta su preferencia por defecto sin haberla configurado', function (): void {
    /** @var TestCase $this */
    actingAsUserId((string) Str::uuid());

    $this->getJson('/api/v1/notification/preferences/me')
        ->assertOk()
        ->assertJsonPath('data.allowed_channels', ['email', 'web', 'mobile', 'internal_message'])
        ->assertJsonPath('data.muted_categories', [])
        ->assertJsonPath('data.frequency', 'immediate')
        ->assertJsonPath('data.consent_given', true);
});

it('el usuario actualiza su preferencia', function (): void {
    /** @var TestCase $this */
    actingAsUserId((string) Str::uuid());

    $this->putJson('/api/v1/notification/preferences/me', [
        'allowed_channels' => ['email'],
        'muted_categories' => ['logro'],
        'frequency' => 'weekly',
        'quiet_hours_start' => '22:00',
        'quiet_hours_end' => '07:00',
    ])
        ->assertOk()
        ->assertJsonPath('data.allowed_channels', ['email'])
        ->assertJsonPath('data.muted_categories', ['logro'])
        ->assertJsonPath('data.frequency', 'weekly')
        ->assertJsonPath('data.quiet_hours_start', '22:00')
        ->assertJsonPath('data.quiet_hours_end', '07:00');
});

it('rechaza un horario de silencio con formato invalido', function (): void {
    /** @var TestCase $this */
    actingAsUserId((string) Str::uuid());

    $this->putJson('/api/v1/notification/preferences/me', [
        'allowed_channels' => ['email'],
        'muted_categories' => [],
        'frequency' => 'immediate',
        'quiet_hours_start' => '25:00',
        'quiet_hours_end' => '07:00',
    ])->assertStatus(422);
});

it('el usuario revoca y vuelve a otorgar su consentimiento', function (): void {
    /** @var TestCase $this */
    actingAsUserId((string) Str::uuid());

    $this->deleteJson('/api/v1/notification/preferences/me/consent')
        ->assertOk()
        ->assertJsonPath('data.consent_given', false);

    $this->postJson('/api/v1/notification/preferences/me/consent')
        ->assertOk()
        ->assertJsonPath('data.consent_given', true);
});

it('descarta el envio de una notificacion cuando el canal no esta permitido por la preferencia del destinatario', function (): void {
    /** @var TestCase $this */
    $userId = (string) Str::uuid();
    actingAsUserId($userId);
    $this->putJson('/api/v1/notification/preferences/me', [
        'allowed_channels' => ['email'],
        'muted_categories' => [],
        'frequency' => 'immediate',
        'quiet_hours_start' => null,
        'quiet_hours_end' => null,
    ])->assertOk();

    actingAsRole(Role::SuperAdmin);
    $this->postJson('/api/v1/notification/notifications', [
        'user_id' => $userId,
        'channel' => 'web',
        'category' => 'logro',
        'subject' => 'Asunto',
        'body' => 'Cuerpo',
    ])
        ->assertOk()
        ->assertJsonPath('data', null);
});

it('requiere autenticacion para todos los endpoints de preferencias', function (): void {
    /** @var TestCase $this */
    $this->getJson('/api/v1/notification/preferences/me')->assertUnauthorized();
    $this->putJson('/api/v1/notification/preferences/me', [])->assertUnauthorized();
    $this->postJson('/api/v1/notification/preferences/me/consent')->assertUnauthorized();
    $this->deleteJson('/api/v1/notification/preferences/me/consent')->assertUnauthorized();
});
