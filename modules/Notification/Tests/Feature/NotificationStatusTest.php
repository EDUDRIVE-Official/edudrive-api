<?php

declare(strict_types=1);

use function Pest\Laravel\getJson;

it('confirma que el módulo de notificaciones está disponible', function (): void {
    getJson('/api/v1/notification/status')
        ->assertOk()
        ->assertJson([
            'data' => [
                'module' => 'Notification',
                'status' => 'available',
                'version' => '1.0.0',
            ],
        ]);
});
