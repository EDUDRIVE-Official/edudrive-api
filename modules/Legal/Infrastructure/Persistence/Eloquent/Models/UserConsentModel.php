<?php

declare(strict_types=1);

namespace Modules\Legal\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_id
 * @property string $policy_key
 * @property int $policy_version
 * @property Carbon $accepted_at
 * @property string|null $guardian_declaration
 * @property Carbon|null $revoked_at
 */
final class UserConsentModel extends Model
{
    use HasUuids;

    protected $table = 'legal_user_consents';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'policy_version' => 'integer',
        ];
    }
}
