<?php

declare(strict_types=1);

namespace Modules\Legal\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RecordConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'policy_key' => ['required', 'string', 'max:100'],
            'guardian_declaration' => ['nullable', 'string', 'max:150'],
        ];
    }
}
