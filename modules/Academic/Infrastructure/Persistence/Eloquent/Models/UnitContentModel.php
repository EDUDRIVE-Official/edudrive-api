<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class UnitContentModel extends Model
{
    protected $table = 'academic_unit_contents';

    protected $primaryKey = 'unit_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return HasMany<LessonModel, $this> */
    public function lessons(): HasMany
    {
        return $this->hasMany(LessonModel::class, 'unit_id')->orderBy('position');
    }
}
