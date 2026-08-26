<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class ExamAttemptQuestionModel extends Model
{
    protected $table = 'academic_exam_attempt_questions';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'position' => 'int',
            'points' => 'int',
            'options' => 'array',
            'correct_response' => 'array',
            'user_response' => 'array',
            'is_correct' => 'bool',
            'answered_at' => 'datetime',
        ];
    }
}
