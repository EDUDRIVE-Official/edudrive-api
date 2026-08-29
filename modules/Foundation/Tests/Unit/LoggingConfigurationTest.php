<?php

declare(strict_types=1);

use Monolog\Formatter\JsonFormatter;

it('escribe los canales de disco en formato json estructurado', function (): void {
    expect(config('logging.channels.single.formatter'))->toBe(JsonFormatter::class)
        ->and(config('logging.channels.daily.formatter'))->toBe(JsonFormatter::class);
});

it('el canal slack usa su propio nivel minimo, independiente de LOG_LEVEL', function (): void {
    expect(config('logging.channels.slack.level'))->toBe('critical');
});

it('no incluye el canal slack en el stack por defecto sin webhook configurado', function (): void {
    expect(config('logging.channels.stack.channels'))->not->toContain('slack');
});
