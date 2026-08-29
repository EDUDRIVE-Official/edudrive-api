<?php

declare(strict_types=1);

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Identity\Domain\ValueObjects\Email;

uses(RefreshDatabase::class);

it('crea la cuenta de prueba en el ambiente local', function (): void {
    app()['env'] = 'local';

    (new DatabaseSeeder)->run();

    expect(app(UserRepository::class)->existsByEmail(Email::fromString('test@example.com')))->toBeTrue();
});

it('crea la cuenta de prueba en el ambiente testing', function (): void {
    app()['env'] = 'testing';

    (new DatabaseSeeder)->run();

    expect(app(UserRepository::class)->existsByEmail(Email::fromString('test@example.com')))->toBeTrue();
});

it('no crea la cuenta de prueba en produccion', function (): void {
    app()['env'] = 'production';

    (new DatabaseSeeder)->run();

    expect(app(UserRepository::class)->existsByEmail(Email::fromString('test@example.com')))->toBeFalse();
});

it('no crea la cuenta de prueba en staging', function (): void {
    app()['env'] = 'staging';

    (new DatabaseSeeder)->run();

    expect(app(UserRepository::class)->existsByEmail(Email::fromString('test@example.com')))->toBeFalse();
});

it('no duplica la cuenta de prueba si ya existe', function (): void {
    app()['env'] = 'local';

    (new DatabaseSeeder)->run();
    (new DatabaseSeeder)->run();

    expect(app(UserRepository::class)->existsByEmail(Email::fromString('test@example.com')))->toBeTrue();
});
