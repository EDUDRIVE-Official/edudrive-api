<?php

declare(strict_types=1);

use Tests\TestCase;

it('confirma que la aplicación está disponible', function (): void {
    /** @var TestCase $this */
    $response = $this->get('/');

    $response->assertOk();
});
