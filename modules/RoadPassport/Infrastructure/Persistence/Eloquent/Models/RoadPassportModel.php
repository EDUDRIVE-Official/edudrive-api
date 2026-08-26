<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class RoadPassportModel extends Model
{
    protected $table = 'road_passports';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return HasMany<RoadPassportHistoryEntryModel, $this> */
    public function historyEntries(): HasMany
    {
        return $this->hasMany(RoadPassportHistoryEntryModel::class, 'road_passport_id')->orderBy('occurred_at');
    }

    /** @return HasMany<RoadPassportEvidenceModel, $this> */
    public function evidenceEntries(): HasMany
    {
        return $this->hasMany(RoadPassportEvidenceModel::class, 'road_passport_id')->orderBy('occurred_at');
    }

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'level' => 'int',
        ];
    }
}
