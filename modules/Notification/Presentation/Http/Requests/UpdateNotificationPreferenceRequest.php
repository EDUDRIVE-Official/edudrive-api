<?php

declare(strict_types=1);

namespace Modules\Notification\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Notification\Domain\Enums\NotificationChannel;
use Modules\Notification\Domain\Enums\NotificationFrequency;

final class UpdateNotificationPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $timeFormat = 'regex:/^([01]\d|2[0-3]):[0-5]\d$/';

        return [
            'allowed_channels' => ['present', 'array'],
            'allowed_channels.*' => ['string', Rule::in(array_map(
                static fn (NotificationChannel $channel): string => $channel->value,
                NotificationChannel::cases(),
            ))],
            'muted_categories' => ['present', 'array'],
            'muted_categories.*' => ['string', 'max:100'],
            'frequency' => ['required', 'string', Rule::in(array_map(
                static fn (NotificationFrequency $frequency): string => $frequency->value,
                NotificationFrequency::cases(),
            ))],
            'quiet_hours_start' => ['nullable', 'string', $timeFormat, 'required_with:quiet_hours_end'],
            'quiet_hours_end' => ['nullable', 'string', $timeFormat, 'required_with:quiet_hours_start'],
        ];
    }
}
