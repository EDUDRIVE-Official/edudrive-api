<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class NotificationPreferenceModel extends Model
{
    protected $table = 'notification_preferences';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'allowed_channels' => 'array',
            'muted_categories' => 'array',
            'consent_given' => 'boolean',
            'consent_updated_at' => 'datetime',
        ];
    }
}
