<?php

declare(strict_types=1);

namespace Modules\Foundation\Tests\Unit\CommandBus;

use Illuminate\Container\Container;
use Modules\Foundation\Application\Commands\Command;
use Modules\Foundation\Infrastructure\Bus\InMemoryMessageHandlerRegistry;
use Modules\Foundation\Infrastructure\Bus\LaravelCommandBus;

it('envía un comando al handler registrado', function (): void {
    $container = new Container;
    $registry = new InMemoryMessageHandlerRegistry;

    $registry->register(
        TestCommand::class,
        TestCommandHandler::class,
    );

    $container->bind(
        TestCommandHandler::class,
        fn (): TestCommandHandler => new TestCommandHandler,
    );

    $bus = new LaravelCommandBus(
        container: $container,
        registry: $registry,
    );

    $result = $bus->dispatch(
        new TestCommand('EDUDRIVE'),
    );

    expect($result)->toBe('Procesado: EDUDRIVE');
});

final readonly class TestCommand implements Command
{
    public function __construct(
        public string $value,
    ) {}
}

final class TestCommandHandler
{
    public function handle(TestCommand $command): string
    {
        return sprintf(
            'Procesado: %s',
            $command->value,
        );
    }
}
