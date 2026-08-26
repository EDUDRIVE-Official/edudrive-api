<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class RoadPassportEvidenceModel extends Model
{
    protected $table = 'road_passport_evidence';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
