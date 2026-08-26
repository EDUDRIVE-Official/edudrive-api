<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ChangeRoadPassportLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'level' => ['required', 'integer', 'min:1'],
        ];
    }
}
