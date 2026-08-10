<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CourseVersionModel extends Model
{
    protected $table = 'academic_course_versions';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return BelongsTo<CourseModel, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseModel::class, 'course_id');
    }

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'snapshot' => 'array',
            'published_at' => 'immutable_datetime',
            'archived_at' => 'immutable_datetime',
        ];
    }
}
