<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $ai_system_id
 * @property string $severity
 * @property string $description
 * @property string $status
 * @property string|null $corrective_actions
 */
final class AiIncidentModel extends Model
{
    protected $table = 'ai_governance_incidents';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'discovered_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }
}
