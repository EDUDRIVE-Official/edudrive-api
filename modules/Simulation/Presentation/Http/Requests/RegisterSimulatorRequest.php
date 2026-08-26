<?php

declare(strict_types=1);

namespace Modules\Simulation\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterSimulatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'device_identifier' => ['required', 'string', 'max:100'],
            'software_version' => ['required', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }
}
