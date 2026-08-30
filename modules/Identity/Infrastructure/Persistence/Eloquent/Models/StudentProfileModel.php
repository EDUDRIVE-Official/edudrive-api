<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $user_id
 * @property string|null $education_level
 * @property string|null $accessibility_needs
 * @property string|null $learning_preferences
 * @property Carbon $updated_at
 */
final class StudentProfileModel extends Model
{
    protected $table = 'student_profiles';

    protected $primaryKey = 'user_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'education_level',
        'accessibility_needs',
        'learning_preferences',
    ];
}
