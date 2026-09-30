<?php

declare(strict_types=1);

use Modules\Notification\Application\Services\MailDeliveryReadiness;

it('reports the local development mailbox as not ready for external delivery', function (): void {
    config()->set('mail.default', 'smtp');
    config()->set('mail.delivery.mode', 'local');
    config()->set('services.postmark.key', null);
    config()->set('mail.from.address', 'noreply@edudrive.cr');
    config()->set('app.url', 'http://localhost:8080');

    $status = app(MailDeliveryReadiness::class)->status();

    expect($status['ready'])->toBeFalse()
        ->and($status['uses_local_mailbox'])->toBeTrue()
        ->and($status['completed'])->toBeLessThan($status['total']);
});

it('requires every postmark production setting before reporting readiness', function (): void {
    config()->set('mail.default', 'postmark');
    config()->set('mail.delivery.mode', 'external');
    config()->set('services.postmark.key', 'server-token-present');
    config()->set('mail.from.address', 'noreply@edudrive.vr506.com');
    config()->set('app.url', 'https://edudrive.vr506.com');

    $status = app(MailDeliveryReadiness::class)->status();

    expect($status['ready'])->toBeTrue()
        ->and($status['completed'])->toBe($status['total'])
        ->and($status['uses_local_mailbox'])->toBeFalse();
});
