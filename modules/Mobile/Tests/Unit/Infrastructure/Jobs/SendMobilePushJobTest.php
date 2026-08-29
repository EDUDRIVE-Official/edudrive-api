<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Context;
use Modules\Mobile\Infrastructure\Jobs\SendMobilePushJob;

it('captura el correlation_id activo al momento de crear el job', function (): void {
    Context::add('correlation_id', 'mi-correlation-id');

    $job = new SendMobilePushJob('push-token', 'titulo', 'cuerpo');

    expect($job->correlationId)->toBe('mi-correlation-id');
});
