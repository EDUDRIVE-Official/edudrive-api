<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $user_id
 * @property string|null $specialties
 * @property string|null $certifications
 * @property Carbon $updated_at
 */
final class TeacherProfileModel extends Model
{
    protected $table = 'teacher_profiles';

    protected $primaryKey = 'user_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'specialties',
        'certifications',
    ];
}
