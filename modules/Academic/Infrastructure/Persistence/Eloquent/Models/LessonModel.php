<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class LessonModel extends Model
{
    protected $table = 'academic_lessons';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return HasMany<ContentBlockModel, $this> */
    public function blocks(): HasMany
    {
        return $this->hasMany(ContentBlockModel::class, 'lesson_id')->orderBy('position');
    }

    protected function casts(): array
    {
        return ['duration_minutes' => 'integer', 'position' => 'integer'];
    }
}
