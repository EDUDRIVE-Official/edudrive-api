<?php

declare(strict_types=1);

namespace Modules\Organization\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\Organization\Domain\Enums\OrganizationType;

final class CreateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:180',
            ],
            'type' => [
                'required',
                'string',
                new Enum(OrganizationType::class),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la organización es obligatorio.',
            'name.max' => 'El nombre no puede superar 180 caracteres.',
            'type.required' => 'El tipo de organización es obligatorio.',
            'type.enum' => 'El tipo de organización no es válido.',
        ];
    }
}
