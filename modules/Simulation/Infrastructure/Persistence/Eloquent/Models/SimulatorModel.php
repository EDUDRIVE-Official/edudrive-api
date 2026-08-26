<?php

declare(strict_types=1);

namespace Modules\Simulation\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SimulatorModel extends Model
{
    protected $table = 'simulators';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return HasMany<SimulatorHistoryEntryModel, $this> */
    public function historyEntries(): HasMany
    {
        return $this->hasMany(SimulatorHistoryEntryModel::class, 'simulator_id')->orderBy('occurred_at');
    }

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
        ];
    }
}
