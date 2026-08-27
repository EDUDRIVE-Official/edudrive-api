<?php

declare(strict_types=1);

use Modules\Admin\Application\Queries\GetAuditLogsQuery;
use Modules\Admin\Application\Responses\AuditLogResponse;
use Modules\Admin\Application\UseCases\GetAuditLogsHandler;
use Modules\Audit\Application\Contracts\AuditRepository;
use Modules\Audit\Application\DTO\AuditEntry;

final class InMemoryAuditRepository implements AuditRepository
{
    /** @var list<AuditEntry> */
    public array $items = [];

    public function save(AuditEntry $entry): void
    {
        $this->items[] = $entry;
    }

    /** @return list<AuditEntry> */
    public function all(): array
    {
        return $this->items;
    }
}

it('lista los registros de auditoria existentes', function (): void {
    $auditLogs = new InMemoryAuditRepository;
    $auditLogs->save(new AuditEntry(
        action: 'user.activated',
        userId: 'user-1',
        entity: 'User',
        entityId: 'user-1',
        metadata: ['source' => 'admin-panel'],
        id: 'entry-1',
        occurredAt: new DateTimeImmutable('2026-08-27T10:00:00+00:00'),
    ));

    $responses = (new GetAuditLogsHandler($auditLogs))->handle(new GetAuditLogsQuery);

    expect($responses)->toHaveCount(1)
        ->and($responses[0])->toBeInstanceOf(AuditLogResponse::class)
        ->and($responses[0]->action)->toBe('user.activated')
        ->and($responses[0]->metadata)->toBe(['source' => 'admin-panel']);
});

it('devuelve una lista vacia cuando no hay registros', function (): void {
    $auditLogs = new InMemoryAuditRepository;

    expect((new GetAuditLogsHandler($auditLogs))->handle(new GetAuditLogsQuery))->toBe([]);
});
