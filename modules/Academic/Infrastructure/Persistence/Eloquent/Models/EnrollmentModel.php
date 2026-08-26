<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class EnrollmentModel extends Model
{
    protected $table = 'academic_enrollments';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'enrolled_at' => 'immutable_datetime',
        ];
    }
}
