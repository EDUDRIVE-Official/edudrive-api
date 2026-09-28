<?php

declare(strict_types=1);

namespace Modules\Notification\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Notification\Domain\Enums\NotificationChannel;

final class SendNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'string', 'uuid', 'exists:users,id'],
            'channel' => ['required', 'string', Rule::in(array_map(
                static fn (NotificationChannel $channel): string => $channel->value,
                NotificationChannel::cases(),
            ))],
            'category' => ['required', 'string', Rule::in(['curso', 'logro', 'certificado', 'recordatorio', 'seguridad', 'sistema', 'acompañamiento'])],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'action_url' => ['nullable', 'string', 'max:1000', 'regex:/^\/(?!\/)/'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'user_id.required' => 'Seleccioná una persona destinataria.',
            'user_id.exists' => 'La persona seleccionada ya no existe.',
            'category.required' => 'Seleccioná el propósito del aviso.',
            'category.in' => 'El propósito seleccionado no es válido.',
        ];
    }
}
