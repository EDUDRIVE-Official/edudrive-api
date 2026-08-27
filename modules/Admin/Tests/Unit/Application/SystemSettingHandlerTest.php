<?php

declare(strict_types=1);

use Modules\Admin\Application\Commands\SetSystemSettingCommand;
use Modules\Admin\Application\Exceptions\SystemSettingNotFound;
use Modules\Admin\Application\Queries\GetSystemSettingQuery;
use Modules\Admin\Application\Queries\ListSystemSettingsQuery;
use Modules\Admin\Application\Responses\SystemSettingResponse;
use Modules\Admin\Application\UseCases\GetSystemSettingHandler;
use Modules\Admin\Application\UseCases\ListSystemSettingsHandler;
use Modules\Admin\Application\UseCases\SetSystemSettingHandler;
use Modules\Admin\Domain\Aggregates\SystemSetting;
use Modules\Admin\Domain\Repositories\SystemSettingRepository;
use Modules\Admin\Domain\ValueObjects\SystemSettingKey;

final class InMemorySystemSettingRepository implements SystemSettingRepository
{
    /** @var array<string, SystemSetting> */
    public array $items = [];

    public function save(SystemSetting $setting): void
    {
        $this->items[$setting->key()->value()] = $setting;
    }

    public function findByKey(SystemSettingKey $key): ?SystemSetting
    {
        return $this->items[$key->value()] ?? null;
    }

    /** @return list<SystemSetting> */
    public function all(): array
    {
        return array_values($this->items);
    }
}

it('crea una configuracion nueva cuando no existia', function (): void {
    $settings = new InMemorySystemSettingRepository;

    $response = (new SetSystemSettingHandler($settings))->handle(new SetSystemSettingCommand('maintenance_mode', 'false'));

    expect($response)->toBeInstanceOf(SystemSettingResponse::class)
        ->and($response->key)->toBe('maintenance_mode')
        ->and($response->value)->toBe('false');
});

it('actualiza el valor de una configuracion existente', function (): void {
    $settings = new InMemorySystemSettingRepository;
    (new SetSystemSettingHandler($settings))->handle(new SetSystemSettingCommand('maintenance_mode', 'false'));

    $response = (new SetSystemSettingHandler($settings))->handle(new SetSystemSettingCommand('maintenance_mode', 'true'));

    expect($response->value)->toBe('true')
        ->and($settings->all())->toHaveCount(1);
});

it('consulta una configuracion por clave', function (): void {
    $settings = new InMemorySystemSettingRepository;
    (new SetSystemSettingHandler($settings))->handle(new SetSystemSettingCommand('maintenance_mode', 'false'));

    $response = (new GetSystemSettingHandler($settings))->handle(new GetSystemSettingQuery('maintenance_mode'));

    expect($response->value)->toBe('false');
});

it('rechaza consultar una configuracion inexistente', function (): void {
    $settings = new InMemorySystemSettingRepository;

    expect(fn () => (new GetSystemSettingHandler($settings))->handle(new GetSystemSettingQuery('inexistente')))
        ->toThrow(SystemSettingNotFound::class);
});

it('lista todas las configuraciones registradas', function (): void {
    $settings = new InMemorySystemSettingRepository;
    (new SetSystemSettingHandler($settings))->handle(new SetSystemSettingCommand('maintenance_mode', 'false'));
    (new SetSystemSettingHandler($settings))->handle(new SetSystemSettingCommand('signup_enabled', 'true'));

    $responses = (new ListSystemSettingsHandler($settings))->handle(new ListSystemSettingsQuery);

    expect($responses)->toHaveCount(2)
        ->and($responses[0])->toBeInstanceOf(SystemSettingResponse::class);
});
