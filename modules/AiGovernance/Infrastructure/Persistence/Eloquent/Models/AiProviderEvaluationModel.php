<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $provider_name
 * @property string $data_location
 * @property string $retention_policy
 * @property string|null $security_review_notes
 * @property string $approval_status
 */
final class AiProviderEvaluationModel extends Model
{
    protected $table = 'ai_governance_provider_evaluations';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'next_review_due_at' => 'datetime',
        ];
    }
}
