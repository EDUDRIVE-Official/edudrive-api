<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Audit\Application\Contracts\AuditRepository;
use Modules\Audit\Application\DTO\AuditEntry;

it('guarda y lista los registros de auditoria', function (): void {
    $repository = app(AuditRepository::class);

    $repository->save(new AuditEntry(
        action: 'user.activated',
        userId: (string) Str::uuid(),
        entity: 'User',
        entityId: (string) Str::uuid(),
        metadata: ['source' => 'admin-panel'],
    ));

    $entries = $repository->all();

    expect($entries)->toHaveCount(1)
        ->and($entries[0]->action)->toBe('user.activated')
        ->and($entries[0]->entity)->toBe('User')
        ->and($entries[0]->metadata)->toBe(['source' => 'admin-panel'])
        ->and($entries[0]->id)->not->toBeNull()
        ->and($entries[0]->occurredAt)->not->toBeNull();
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
