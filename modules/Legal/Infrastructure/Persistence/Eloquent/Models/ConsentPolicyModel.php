<?php

declare(strict_types=1);

namespace Modules\Legal\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $key
 * @property int $version
 * @property Carbon $effective_at
 */
final class ConsentPolicyModel extends Model
{
    use HasUuids;

    protected $table = 'legal_consent_policies';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'effective_at' => 'datetime',
            'version' => 'integer',
        ];
    }
}
