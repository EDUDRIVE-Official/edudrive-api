<?php

declare(strict_types=1);

namespace Modules\Academic\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProgramModel extends Model
{
    protected $table = 'academic_programs';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    /** @return HasMany<ProgramCourseModel, $this> */
    public function courses(): HasMany
    {
        return $this->hasMany(ProgramCourseModel::class, 'program_id')->orderBy('position');
    }

    /** @return HasMany<ProgramLicenseStageModel, $this> */
    public function licenseStages(): HasMany
    {
        return $this->hasMany(ProgramLicenseStageModel::class, 'program_id')->orderBy('position');
    }

    /** @return HasMany<ProgramContextModel, $this> */
    public function contexts(): HasMany
    {
        return $this->hasMany(ProgramContextModel::class, 'program_id')->orderBy('position');
    }

    /** @return HasMany<ProgramVehicleTypeModel, $this> */
    public function vehicleTypes(): HasMany
    {
        return $this->hasMany(ProgramVehicleTypeModel::class, 'program_id')->orderBy('position');
    }

    protected function casts(): array
    {
        return [
            'min_age' => 'integer',
            'max_age' => 'integer',
            'published_at' => 'immutable_datetime',
            'archived_at' => 'immutable_datetime',
        ];
    }
}
