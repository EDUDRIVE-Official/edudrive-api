<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ExamAttemptModel extends Model
{
    protected $table = 'academic_exam_attempts';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return HasMany<ExamAttemptQuestionModel, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(ExamAttemptQuestionModel::class, 'attempt_id')->orderBy('position');
    }

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'grading_breakdown' => 'array',
            'competency_results' => 'array',
            'duration_minutes' => 'int',
            'passing_score' => 'int',
            'shuffle_questions' => 'bool',
            'feedback_mode' => 'string',
            'score' => 'int',
            'total_points' => 'int',
            'percentage' => 'int',
            'passed' => 'bool',
        ];
    }
}
