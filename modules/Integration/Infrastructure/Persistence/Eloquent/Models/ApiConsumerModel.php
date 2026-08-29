<?php

declare(strict_types=1);

namespace Modules\Integration\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property array<int, string> $scopes
 * @property string $status
 * @property string $integration_key_hash
 * @property Carbon|null $expires_at
 * @property Carbon $issued_at
 */
final class ApiConsumerModel extends Model
{
    protected $table = 'integration_api_consumers';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return HasMany<ApiConsumerHistoryEntryModel, $this> */
    public function historyEntries(): HasMany
    {
        return $this->hasMany(ApiConsumerHistoryEntryModel::class, 'api_consumer_id')->orderBy('occurred_at');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'expires_at' => 'datetime',
            'issued_at' => 'datetime',
        ];
    }
}
