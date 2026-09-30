<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class StudentLearningResetModel extends Model
{
    protected $table = 'identity_student_learning_resets';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'summary' => 'array',
            'encrypted_snapshot' => 'encrypted:array',
            'reset_at' => 'immutable_datetime',
        ];
    }
}
