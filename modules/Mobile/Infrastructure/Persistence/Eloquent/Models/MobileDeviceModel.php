<?php

declare(strict_types=1);

namespace Modules\Mobile\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $user_id
 * @property string $device_id
 * @property string $platform
 * @property string|null $push_token
 * @property string $app_version
 */
final class MobileDeviceModel extends Model
{
    protected $table = 'mobile_devices';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }
}
