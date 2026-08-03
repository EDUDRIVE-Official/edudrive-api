<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CompetencyModel extends Model
{
    protected $table = 'academic_competencies';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    /** @return HasMany<SubcompetencyModel, $this> */
    public function subcompetencies(): HasMany
    {
        return $this->hasMany(SubcompetencyModel::class, 'competency_id')->orderBy('position');
    }
}
