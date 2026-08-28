<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Modules\Audit\Application\Contracts\AuditRepository;
use Modules\Audit\Application\DTO\AuditEntry;
use Modules\Audit\Infrastructure\Services\DatabaseAuditLogger;

final class RecordingAuditRepository implements AuditRepository
{
    /** @var list<AuditEntry> */
    public array $saved = [];

    public function save(AuditEntry $entry): void
    {
        $this->saved[] = $entry;
    }

    /** @return list<AuditEntry> */
    public function all(): array
    {
        return $this->saved;
    }
}

it('completa ip y correlation id desde la peticion y el contexto cuando el llamador no los provee', function (): void {
    $repository = new RecordingAuditRepository;
    $request = Request::create('/api/v1/example', 'POST', server: ['REMOTE_ADDR' => '198.51.100.7']);

    Context::add('correlation_id', 'corr-from-context');

    $logger = new DatabaseAuditLogger($repository, $request);
    $logger->log(new AuditEntry(action: 'auth.login'));

    expect($repository->saved)->toHaveCount(1)
        ->and($repository->saved[0]->ip)->toBe('198.51.100.7')
        ->and($repository->saved[0]->correlationId)->toBe('corr-from-context');

    Context::forget('correlation_id');
});

it('respeta la ip y el correlation id explicitos provistos por el llamador', function (): void {
    $repository = new RecordingAuditRepository;
    $request = Request::create('/api/v1/example', 'POST', server: ['REMOTE_ADDR' => '198.51.100.7']);

    $logger = new DatabaseAuditLogger($repository, $request);
    $logger->log(new AuditEntry(action: 'auth.login', ip: '10.0.0.1', correlationId: 'explicit-corr'));

    expect($repository->saved[0]->ip)->toBe('10.0.0.1')
        ->and($repository->saved[0]->correlationId)->toBe('explicit-corr');
});
