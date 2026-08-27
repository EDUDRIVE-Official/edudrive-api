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
            'user_id' => ['required', 'string', 'uuid'],
            'channel' => ['required', 'string', Rule::in(array_map(
                static fn (NotificationChannel $channel): string => $channel->value,
                NotificationChannel::cases(),
            ))],
            'category' => ['required', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ];
    }
}
