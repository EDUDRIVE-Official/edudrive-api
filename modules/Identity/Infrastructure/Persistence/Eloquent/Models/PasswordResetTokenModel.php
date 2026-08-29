<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $email
 * @property string $token
 * @property Carbon|null $created_at
 */
final class PasswordResetTokenModel extends Model
{
    protected $table = 'password_reset_tokens';

    protected $primaryKey = 'email';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'email',
        'token',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
