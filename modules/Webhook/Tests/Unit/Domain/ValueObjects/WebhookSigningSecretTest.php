<?php

declare(strict_types=1);

use Modules\Webhook\Domain\ValueObjects\WebhookSigningSecret;

it('genera un secreto aleatorio de 64 caracteres hexadecimales', function (): void {
    $secret = WebhookSigningSecret::generate();

    expect($secret->value())->toMatch('/^[0-9a-f]{64}$/');
});

it('genera valores distintos en cada llamada', function (): void {
    expect(WebhookSigningSecret::generate()->value())->not->toBe(WebhookSigningSecret::generate()->value());
});

it('firma un payload de forma determinista con el mismo secreto', function (): void {
    $secret = WebhookSigningSecret::fromPlainValue('secreto-fijo');

    $signatureA = $secret->sign('{"a":1}');
    $signatureB = $secret->sign('{"a":1}');

    expect($signatureA)->toBe($signatureB)
        ->and($signatureA)->toBe(hash_hmac('sha256', '{"a":1}', 'secreto-fijo'));
});

it('produce firmas distintas para payloads distintos', function (): void {
    $secret = WebhookSigningSecret::fromPlainValue('secreto-fijo');

    expect($secret->sign('{"a":1}'))->not->toBe($secret->sign('{"a":2}'));
});

it('produce firmas distintas para secretos distintos sobre el mismo payload', function (): void {
    $secretA = WebhookSigningSecret::fromPlainValue('secreto-a');
    $secretB = WebhookSigningSecret::fromPlainValue('secreto-b');

    expect($secretA->sign('{"a":1}'))->not->toBe($secretB->sign('{"a":1}'));
});
