<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CourseModel extends Model
{
    protected $table = 'academic_courses';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return HasMany<CourseModuleModel, $this> */
    public function modules(): HasMany
    {
        return $this->hasMany(CourseModuleModel::class, 'course_id')->orderBy('position');
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'immutable_datetime',
            'archived_at' => 'immutable_datetime',
            'duration_hours' => 'integer',
        ];
    }
}
