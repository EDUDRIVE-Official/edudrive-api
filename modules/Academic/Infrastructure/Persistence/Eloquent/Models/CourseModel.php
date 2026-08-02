<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class CourseModel extends Model
{
    protected $table = 'academic_courses';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'published_at' => 'immutable_datetime',
            'archived_at' => 'immutable_datetime',
            'duration_hours' => 'integer',
        ];
    }
}
