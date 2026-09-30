<?php

declare(strict_types=1);

namespace Modules\Legal\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PublishPolicyVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', Rule::in(['privacy_policy', 'terms_of_service', 'minor_consent'])],
            'effective_at' => ['nullable', 'date'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'key.required' => 'Seleccioná una política.',
            'key.in' => 'La política seleccionada no es válida.',
        ];
    }
}
