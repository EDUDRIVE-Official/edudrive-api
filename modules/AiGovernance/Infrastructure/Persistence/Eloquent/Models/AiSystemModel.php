<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $name
 * @property string $purpose
 * @property string $functional_owner_id
 * @property string|null $technical_owner_id
 * @property string $risk_level
 * @property int $supervision_level
 * @property array<int, string> $data_categories
 * @property string $status
 * @property bool $extraordinary_approval_granted
 * @property bool $committee_approved
 * @property string|null $provider_evaluation_id
 */
final class AiSystemModel extends Model
{
    protected $table = 'ai_governance_systems';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'data_categories' => 'array',
            'extraordinary_approval_granted' => 'boolean',
            'extraordinary_approval_at' => 'datetime',
            'committee_approved' => 'boolean',
            'committee_approved_at' => 'datetime',
            'registered_at' => 'datetime',
        ];
    }
}
