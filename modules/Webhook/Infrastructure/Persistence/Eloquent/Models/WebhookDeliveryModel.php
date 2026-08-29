<?php

declare(strict_types=1);

namespace Modules\Webhook\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $subscription_id
 * @property string $event_name
 * @property array<string, mixed> $payload
 * @property string $status
 * @property int $attempts
 * @property Carbon|null $last_attempted_at
 * @property int|null $last_response_status
 * @property string|null $last_response_body
 * @property Carbon|null $next_retry_at
 */
final class WebhookDeliveryModel extends Model
{
    protected $table = 'webhook_deliveries';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'last_attempted_at' => 'datetime',
            'next_retry_at' => 'datetime',
        ];
    }
}
