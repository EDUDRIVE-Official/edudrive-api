<?php

declare(strict_types=1);

namespace Modules\Simulation\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ScheduleSimulationSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'simulator_id' => ['required', 'string', 'uuid'],
            'vehicle_type' => ['required', 'string', 'max:100'],
            'scenario' => ['required', 'string', 'max:100'],
            'scheduled_at' => ['required', 'date'],
            'planned_duration_minutes' => ['required', 'integer', 'min:1'],
        ];
    }
}
