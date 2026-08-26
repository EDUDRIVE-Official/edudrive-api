<?php

declare(strict_types=1);

namespace Modules\Simulation\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Simulation\Domain\Enums\DecisionRiskLevel;
use Modules\Simulation\Domain\Enums\DriverReactionType;

final class SubmitDecisionPointsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'decisions' => ['array'],
            'decisions.*.road_context' => ['required', 'string', 'max:255'],
            'decisions.*.risk_level' => ['required', 'string', Rule::in(array_map(
                static fn (DecisionRiskLevel $level): string => $level->value,
                DecisionRiskLevel::cases(),
            ))],
            'decisions.*.driver_reaction' => ['required', 'string', Rule::in(array_map(
                static fn (DriverReactionType $type): string => $type->value,
                DriverReactionType::cases(),
            ))],
            'decisions.*.occurred_at' => ['required', 'date'],
        ];
    }
}
