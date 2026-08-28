<?php

declare(strict_types=1);

use Tests\TestCase;

it('limita la verificacion publica de certificados a 30 por minuto', function (): void {
    /** @var TestCase $this */
    for ($attempt = 1; $attempt <= 30; $attempt++) {
        $this->getJson('/api/v1/certification/verify/AAAA-AAAA-AAAA');
    }

    $this->getJson('/api/v1/certification/verify/AAAA-AAAA-AAAA')
        ->assertStatus(429)
        ->assertJsonPath('code', 'TOO_MANY_REQUESTS');
});
