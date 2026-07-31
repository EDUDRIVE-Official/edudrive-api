<?php

declare(strict_types=1);

namespace Modules\Authorization\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\Authorization\Domain\Enums\Role;

final class AssignRoleRequest extends FormRequest
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
            'user_id' => [
                'required',
                'uuid',
            ],
            'role' => [
                'required',
                'string',
                new Enum(Role::class),
            ],
            'organization_id' => [
                'nullable',
                'uuid',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'El identificador del usuario es obligatorio.',
            'user_id.uuid' => 'El identificador del usuario debe ser un UUID válido.',
            'role.required' => 'El rol es obligatorio.',
            'role.enum' => 'El rol no es válido.',
            'organization_id.uuid' => 'El identificador de la organización debe ser un UUID válido.',
        ];
    }
}
