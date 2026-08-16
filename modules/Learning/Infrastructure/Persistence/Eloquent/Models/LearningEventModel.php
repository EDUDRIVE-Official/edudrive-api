<?php

declare(strict_types=1);

namespace Modules\Learning\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $enrollment_id
 * @property string $user_id
 * @property string $course_id
 * @property string $verb
 * @property string $subject_id
 * @property array<string, mixed> $evidence
 * @property Carbon $occurred_at
 */
final class LearningEventModel extends Model
{
    use HasUuids;

    protected $table = 'learning_events';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'evidence' => 'array',
        'occurred_at' => 'datetime',
    ];
}
