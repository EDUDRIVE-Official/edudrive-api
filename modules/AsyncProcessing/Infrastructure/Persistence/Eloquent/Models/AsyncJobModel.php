<?php

declare(strict_types=1);

namespace Modules\AsyncProcessing\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $type
 * @property string|null $requested_by_user_id
 * @property string $status
 * @property array<string, mixed>|null $result
 * @property string|null $failure_reason
 */
final class AsyncJobModel extends Model
{
    protected $table = 'async_jobs';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'result' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
