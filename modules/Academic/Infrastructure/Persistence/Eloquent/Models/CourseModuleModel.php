<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CourseModuleModel extends Model
{
    protected $table = 'academic_course_modules';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return HasMany<CourseUnitModel, $this> */
    public function units(): HasMany
    {
        return $this->hasMany(CourseUnitModel::class, 'module_id')->orderBy('position');
    }

    /** @return BelongsToMany<CourseModuleModel, $this> */
    public function prerequisiteModules(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'academic_module_prerequisites',
            'module_id',
            'prerequisite_module_id',
        )->orderBy('academic_course_modules.position');
    }

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'position' => 'integer',
        ];
    }
}
