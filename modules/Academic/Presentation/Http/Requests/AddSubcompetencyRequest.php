<?php

declare(strict_types=1);

namespace Modules\Academic\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AddSubcompetencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:70', 'regex:/^[A-Za-z0-9]+(?:[.-][A-Za-z0-9]+)*$/'],
            'title' => ['required', 'string', 'max:180'],
        ];
    }
}
