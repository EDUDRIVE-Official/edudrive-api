<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class QuestionModel extends Model
{
    protected $table = 'academic_questions';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return HasMany<QuestionOptionModel, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(QuestionOptionModel::class, 'question_id')->orderBy('position');
    }

    protected function casts(): array
    {
        return [
            'score' => 'int',
            'media' => 'array',
            'response' => 'array',
        ];
    }
}
