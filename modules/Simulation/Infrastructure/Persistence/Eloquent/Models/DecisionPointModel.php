<?php

declare(strict_types=1);

namespace Modules\Simulation\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class DecisionPointModel extends Model
{
    protected $table = 'decision_points';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }
}
