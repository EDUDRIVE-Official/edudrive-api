<?php

declare(strict_types=1);

use Modules\Foundation\Application\Exceptions\MissingRequiredSecrets;
use Modules\Foundation\Infrastructure\Environment\RequiredSecretsValidator;

it('no lanza ninguna excepcion cuando todos los valores requeridos estan presentes', function (): void {
    (new RequiredSecretsValidator)->ensureAllPresent([
        'APP_KEY' => 'base64:algo',
        'DB_PASSWORD' => 'una-contrasena',
    ]);
})->throwsNoExceptions();

it('lanza una excepcion listando las variables faltantes', function (): void {
    expect(fn () => (new RequiredSecretsValidator)->ensureAllPresent([
        'APP_KEY' => 'base64:algo',
        'DB_PASSWORD' => null,
        'AWS_ACCESS_KEY_ID' => '',
    ]))
        ->toThrow(MissingRequiredSecrets::class, 'DB_PASSWORD, AWS_ACCESS_KEY_ID');
});

it('trata una cadena vacia como valor faltante', function (): void {
    expect(fn () => (new RequiredSecretsValidator)->ensureAllPresent(['AWS_BUCKET' => '']))
        ->toThrow(MissingRequiredSecrets::class);
});
