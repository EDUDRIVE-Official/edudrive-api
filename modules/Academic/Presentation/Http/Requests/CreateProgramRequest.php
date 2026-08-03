<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\Academic\Domain\Enums\LicenseStage;
use Modules\Academic\Domain\Enums\ProgramContext;
use Modules\Academic\Domain\Enums\VehicleType;

final class CreateProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*$/'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string'],
            'min_age' => ['nullable', 'integer', 'min:0'],
            'max_age' => ['nullable', 'integer', 'min:0'],
            'license_stages' => ['array'],
            'license_stages.*' => [new Enum(LicenseStage::class)],
            'contexts' => ['array'],
            'contexts.*' => [new Enum(ProgramContext::class)],
            'vehicle_types' => ['array'],
            'vehicle_types.*' => [new Enum(VehicleType::class)],
        ];
    }
}
