<?php

declare(strict_types=1);

namespace Modules\Webhook\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $url
 * @property string $secret_encrypted
 * @property array<int, string> $events
 * @property string $status
 */
final class WebhookSubscriptionModel extends Model
{
    protected $table = 'webhook_subscriptions';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return HasMany<WebhookDeliveryModel, $this> */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDeliveryModel::class, 'subscription_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'events' => 'array',
        ];
    }
}
