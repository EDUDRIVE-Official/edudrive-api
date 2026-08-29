<?php

declare(strict_types=1);

namespace Modules\Webhook\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Webhook\Domain\Enums\WebhookDeliveryStatus;

final class ListWebhookDeliveriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::in(array_map(
                static fn (WebhookDeliveryStatus $status): string => $status->value,
                WebhookDeliveryStatus::cases(),
            ))],
        ];
    }
}
