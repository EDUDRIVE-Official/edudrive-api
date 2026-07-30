<?php

declare(strict_types=1);

namespace Modules\Foundation\Tests\Unit\QueryBus;

use Illuminate\Container\Container;
use Modules\Foundation\Application\Queries\Query;
use Modules\Foundation\Infrastructure\Bus\InMemoryMessageHandlerRegistry;
use Modules\Foundation\Infrastructure\Bus\LaravelQueryBus;

it('envía una consulta al handler registrado', function (): void {
    $container = new Container;
    $registry = new InMemoryMessageHandlerRegistry;

    $registry->register(
        TestQuery::class,
        TestQueryHandler::class,
    );

    $container->bind(
        TestQueryHandler::class,
        fn (): TestQueryHandler => new TestQueryHandler,
    );

    $bus = new LaravelQueryBus(
        container: $container,
        registry: $registry,
    );

    $result = $bus->ask(
        new TestQuery('EDU-001'),
    );

    expect($result)->toBe([
        'code' => 'EDU-001',
        'status' => 'draft',
    ]);
});

final readonly class TestQuery implements Query
{
    public function __construct(
        public string $code,
    ) {}
}

final class TestQueryHandler
{
    /**
     * @return array{code: string, status: string}
     */
    public function handle(TestQuery $query): array
    {
        return [
            'code' => $query->code,
            'status' => 'draft',
        ];
    }
}
