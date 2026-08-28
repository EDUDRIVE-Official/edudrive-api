<?php

declare(strict_types=1);

use Modules\Foundation\Infrastructure\Security\SecretPatternScanner;

it('detecta un access key id de aws', function (): void {
    $matches = (new SecretPatternScanner)->scan("AWS_ACCESS_KEY_ID=AKIAABCDEFGHIJKLMNOP\n");

    expect($matches)->toBe(['AWS Access Key ID']);
});

it('detecta una secret access key de aws asignada por su nombre', function (): void {
    $matches = (new SecretPatternScanner)->scan(
        "aws_secret_access_key = 'abcdefghijklmnopqrstuvwxyz0123456789ABCD'\n",
    );

    expect($matches)->toBe(['AWS Secret Access Key']);
});

it('detecta un bloque de llave privada pem', function (): void {
    $matches = (new SecretPatternScanner)->scan('-----BEGIN RSA PRIVATE KEY-----');

    expect($matches)->toBe(['Bloque de llave privada']);
});

it('detecta un webhook de slack', function (): void {
    $matches = (new SecretPatternScanner)->scan(
        'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX',
    );

    expect($matches)->toBe(['Webhook de Slack']);
});

it('no reporta nada para una linea de codigo normal', function (): void {
    $matches = (new SecretPatternScanner)->scan("'aws_secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),\n");

    expect($matches)->toBe([]);
});

it('no reporta nada para un placeholder de env.example', function (): void {
    $matches = (new SecretPatternScanner)->scan('AWS_SECRET_ACCESS_KEY=');

    expect($matches)->toBe([]);
});
