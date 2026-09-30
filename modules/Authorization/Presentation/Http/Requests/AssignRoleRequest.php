<?php

declare(strict_types=1);

namespace Modules\Authorization\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
                'exists:users,id',
            ],
            'role' => [
                'required',
                'string',
                new Enum(Role::class),
            ],
            'organization_id' => [
                Rule::requiredIf(fn (): bool => $this->input('role') === Role::InstitutionalAdmin->value),
                'nullable',
                'uuid',
                'exists:organizations,id',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'Seleccioná un usuario.',
            'user_id.uuid' => 'El usuario seleccionado no es válido.',
            'user_id.exists' => 'El usuario seleccionado ya no existe.',
            'role.required' => 'El rol es obligatorio.',
            'role.enum' => 'El rol no es válido.',
            'organization_id.uuid' => 'La organización seleccionada no es válida.',
            'organization_id.exists' => 'La organización seleccionada ya no existe.',
            'organization_id.required' => 'Un administrador institucional debe pertenecer a una organización.',
        ];
    }
}
