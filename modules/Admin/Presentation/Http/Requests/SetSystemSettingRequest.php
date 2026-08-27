<?php

declare(strict_types=1);

namespace Modules\Admin\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SetSystemSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'value' => ['required', 'string'],
        ];
    }
}
