<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class EnrollmentLessonCompletionModel extends Model
{
    protected $table = 'academic_enrollment_lesson_completions';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'completed_at' => 'immutable_datetime',
        ];
    }
}
