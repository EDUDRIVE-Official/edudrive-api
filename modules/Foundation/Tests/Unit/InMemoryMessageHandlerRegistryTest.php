<?php

declare(strict_types=1);

namespace Modules\Foundation\Tests\Unit\Registry;

use Modules\Foundation\Application\Bus\Exceptions\MessageHandlerNotFound;
use Modules\Foundation\Infrastructure\Bus\InMemoryMessageHandlerRegistry;

it('registra y recupera el handler de un mensaje', function (): void {
    $registry = new InMemoryMessageHandlerRegistry;

    $registry->register(
        ExampleCommand::class,
        ExampleCommandHandler::class,
    );

    expect(
        $registry->handlerFor(ExampleCommand::class),
    )->toBe(ExampleCommandHandler::class);
});

it('lanza una excepción cuando no existe un handler', function (): void {
    $registry = new InMemoryMessageHandlerRegistry;

    expect(
        fn (): string => $registry->handlerFor(
            UnregisteredCommand::class,
        ),
    )->toThrow(MessageHandlerNotFound::class);
});

final class ExampleCommand {}

final class ExampleCommandHandler {}

final class UnregisteredCommand {}
