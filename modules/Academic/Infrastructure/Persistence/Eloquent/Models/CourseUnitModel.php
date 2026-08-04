<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class CourseUnitModel extends Model
{
    protected $table = 'academic_course_units';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return BelongsToMany<CourseUnitModel, $this> */
    public function prerequisiteUnits(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'academic_unit_prerequisites',
            'unit_id',
            'prerequisite_unit_id',
        )->orderBy('academic_course_units.position');
    }

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'position' => 'integer',
        ];
    }
}
