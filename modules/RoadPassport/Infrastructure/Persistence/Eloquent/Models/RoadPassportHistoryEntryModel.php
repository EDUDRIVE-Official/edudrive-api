<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class RoadPassportHistoryEntryModel extends Model
{
    protected $table = 'road_passport_history_entries';

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
