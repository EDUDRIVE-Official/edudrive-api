<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SubcompetencyModel extends Model
{
    protected $table = 'academic_subcompetencies';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return HasMany<CompetencyIndicatorModel, $this> */
    public function indicators(): HasMany
    {
        return $this->hasMany(CompetencyIndicatorModel::class, 'subcompetency_id')->orderBy('position');
    }
}
