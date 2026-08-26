<?php

declare(strict_types=1);

namespace Modules\Gamification\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class UserBadgeModel extends Model
{
    protected $table = 'user_badges';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'earned_at' => 'datetime',
        ];
    }
}
