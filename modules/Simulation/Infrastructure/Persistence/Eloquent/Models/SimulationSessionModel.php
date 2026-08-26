<?php

declare(strict_types=1);

namespace Modules\Simulation\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SimulationSessionModel extends Model
{
    protected $table = 'simulation_sessions';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return HasMany<SimulationSessionHistoryEntryModel, $this> */
    public function historyEntries(): HasMany
    {
        return $this->hasMany(SimulationSessionHistoryEntryModel::class, 'simulation_session_id')->orderBy('occurred_at');
    }

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }
}
