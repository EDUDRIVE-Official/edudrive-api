<?php

declare(strict_types=1);

namespace Modules\Integration\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $api_consumer_id
 * @property string $from_status
 * @property string $to_status
 * @property string|null $reason
 * @property Carbon $occurred_at
 */
final class ApiConsumerHistoryEntryModel extends Model
{
    protected $table = 'integration_api_consumer_history_entries';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }
}
