<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $type
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, CampusModel> $campuses
 */
final class OrganizationModel extends Model
{
    protected $table = 'organizations';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'name',
        'type',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasMany<CampusModel, $this>
     */
    public function campuses(): HasMany
    {
        return $this->hasMany(CampusModel::class, 'organization_id');
    }
}
