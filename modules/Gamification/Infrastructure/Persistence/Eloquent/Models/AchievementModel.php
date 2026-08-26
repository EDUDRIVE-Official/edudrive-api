<?php

declare(strict_types=1);

namespace Modules\Gamification\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class AchievementModel extends Model
{
    protected $table = 'achievements';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'retired_at' => 'datetime',
        ];
    }
}
