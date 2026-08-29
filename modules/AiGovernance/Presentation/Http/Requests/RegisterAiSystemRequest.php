<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterAiSystemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'purpose' => ['required', 'string'],
            'functional_owner_id' => ['required', 'string', 'max:100'],
            'technical_owner_id' => ['nullable', 'string', 'max:100'],
            'risk_level' => ['required', 'string'],
            'supervision_level' => ['required', 'integer', 'min:1', 'max:4'],
            'data_categories' => ['present', 'array'],
            'data_categories.*' => ['string'],
            'provider_evaluation_id' => ['nullable', 'uuid'],
        ];
    }
}
