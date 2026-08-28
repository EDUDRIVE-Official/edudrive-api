<?php

declare(strict_types=1);

use Modules\Foundation\Infrastructure\Security\SecretPatternScanner;
use Modules\Foundation\Presentation\Console\ScanForSecretsCommand;

it('reporta el numero de linea y la etiqueta de cada coincidencia', function (): void {
    $command = new ScanForSecretsCommand;

    $violations = $command->findViolations(new SecretPatternScanner, [
        'const foo = 1;',
        'AWS_ACCESS_KEY_ID=AKIAABCDEFGHIJKLMNOP',
        '-----BEGIN RSA PRIVATE KEY-----',
    ]);

    expect($violations)->toBe([
        [2, 'AWS Access Key ID'],
        [3, 'Bloque de llave privada'],
    ]);
});

it('no reporta nada cuando ninguna linea contiene un secreto', function (): void {
    $command = new ScanForSecretsCommand;

    $violations = $command->findViolations(new SecretPatternScanner, [
        'const foo = 1;',
        "'aws_secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),",
    ]);

    expect($violations)->toBe([]);
});
