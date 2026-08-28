<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Audit\Application\Contracts\AuditRepository;
use Modules\Audit\Application\DTO\AuditEntry;

it('guarda y lista los registros de auditoria', function (): void {
    $repository = app(AuditRepository::class);

    $repository->save(new AuditEntry(
        action: 'user.activated',
        entity: 'User',
        entityId: (string) Str::uuid(),
        metadata: ['source' => 'admin-panel'],
    ));

    $entries = $repository->all();

    expect($entries)->toHaveCount(1)
        ->and($entries[0]->action)->toBe('user.activated')
        ->and($entries[0]->entity)->toBe('User')
        ->and($entries[0]->metadata)->toBe(['source' => 'admin-panel'])
        ->and($entries[0]->outcome)->toBe('success')
        ->and($entries[0]->id)->not->toBeNull()
        ->and($entries[0]->occurredAt)->not->toBeNull();
});

it('guarda y recupera la ip, el correlation id y el resultado', function (): void {
    $repository = app(AuditRepository::class);

    $repository->save(new AuditEntry(
        action: 'auth.login',
        ip: '203.0.113.10',
        correlationId: 'corr-1234',
        outcome: 'failure',
    ));

    $entries = $repository->all();

    expect($entries)->toHaveCount(1)
        ->and($entries[0]->ip)->toBe('203.0.113.10')
        ->and($entries[0]->correlationId)->toBe('corr-1234')
        ->and($entries[0]->outcome)->toBe('failure');
});

it('lista los registros mas recientes primero', function (): void {
    $repository = app(AuditRepository::class);

    $repository->save(new AuditEntry(action: 'first.action'));
    $repository->save(new AuditEntry(action: 'second.action'));

    $entries = $repository->all();

    expect($entries)->toHaveCount(2)
        ->and($entries[0]->action)->toBe('second.action')
        ->and($entries[1]->action)->toBe('first.action');
});
