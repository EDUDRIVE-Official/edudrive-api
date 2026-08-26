<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ExamModel extends Model
{
    protected $table = 'academic_exams';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return HasMany<ExamQuestionModel, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestionModel::class, 'exam_id')->orderBy('position');
    }

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'int',
            'max_attempts' => 'int',
            'passing_score' => 'int',
            'shuffle_questions' => 'bool',
            'feedback_mode' => 'string',
            'kind' => 'string',
            'allow_partial_credit' => 'bool',
            'apply_penalties' => 'bool',
        ];
    }
}
