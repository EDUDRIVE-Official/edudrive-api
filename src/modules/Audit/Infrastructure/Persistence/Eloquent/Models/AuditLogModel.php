<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string|null $user_id
 * @property string $action
 * @property string|null $entity
 * @property string|null $entity_id
 * @property string|null $ip
 * @property string|null $user_agent
 * @property array<string, mixed>|null $metadata
 * @property Carbon $occurred_at
 */
final class AuditLogModel extends Model
{
    use HasUuids;

    protected $table = 'audit_logs';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];
}
