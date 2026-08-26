<?php

declare(strict_types=1);

namespace Modules\Simulation\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Simulation\Domain\Enums\TelemetryEventType;

final class SubmitTelemetryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'samples' => ['array'],
            'samples.*.id' => ['required', 'uuid'],
            'samples.*.speed_kph' => ['required', 'numeric', 'min:0'],
            'samples.*.braking_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'samples.*.acceleration_mps2' => ['required', 'numeric'],
            'samples.*.steering_angle_degrees' => ['required', 'numeric'],
            'samples.*.recorded_at' => ['required', 'date'],
            'events' => ['array'],
            'events.*.id' => ['required', 'uuid'],
            'events.*.type' => ['required', 'string', Rule::in(array_map(
                static fn (TelemetryEventType $type): string => $type->value,
                TelemetryEventType::cases(),
            ))],
            'events.*.details' => ['nullable', 'string', 'max:255'],
            'events.*.occurred_at' => ['required', 'date'],
        ];
    }
}
