<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $name
 * @property string $provider
 * @property string $version
 * @property string|null $owner_id
 * @property string|null $use_case
 * @property string $status
 * @property string|null $known_risks
 */
final class AiModelModel extends Model
{
    protected $table = 'ai_governance_models';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
        ];
    }
}
