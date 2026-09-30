<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Persistence\Eloquent\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $user_id
 * @property string $organization_id
 * @property string $status
 * @property CarbonImmutable $requested_at
 * @property string|null $reviewed_by
 * @property CarbonImmutable|null $reviewed_at
 */
final class OrganizationMembershipRequestModel extends Model
{
    protected $table = 'identity_organization_membership_requests';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'user_id', 'organization_id', 'status', 'requested_at', 'reviewed_by', 'reviewed_at'];

    protected function casts(): array
    {
        return [
            'requested_at' => 'immutable_datetime',
            'reviewed_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
