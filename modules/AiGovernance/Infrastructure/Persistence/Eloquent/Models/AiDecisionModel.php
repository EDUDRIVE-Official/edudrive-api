<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $ai_system_id
 * @property string|null $requested_by_user_id
 * @property string $input_summary
 * @property string $output_summary
 * @property float|null $confidence_level
 * @property int|null $tokens_input
 * @property int|null $tokens_output
 * @property float|null $cost_amount
 * @property int|null $latency_ms
 * @property string $review_status
 * @property string|null $reviewed_by_user_id
 */
final class AiDecisionModel extends Model
{
    protected $table = 'ai_governance_decisions';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'confidence_level' => 'float',
            'cost_amount' => 'float',
            'reviewed_at' => 'datetime',
            'occurred_at' => 'datetime',
        ];
    }
}
