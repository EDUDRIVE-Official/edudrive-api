<?php

declare(strict_types=1);

namespace Modules\Certification\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CertificateModel extends Model
{
    protected $table = 'certificates';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return HasMany<CertificateHistoryEntryModel, $this> */
    public function historyEntries(): HasMany
    {
        return $this->hasMany(CertificateHistoryEntryModel::class, 'certificate_id')->orderBy('occurred_at');
    }

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
