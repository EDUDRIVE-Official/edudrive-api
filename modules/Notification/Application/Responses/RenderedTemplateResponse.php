<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Responses;

use Modules\Notification\Domain\ValueObjects\RenderedTemplate;

final readonly class RenderedTemplateResponse
{
    public function __construct(
        public string $subject,
        public string $body,
    ) {}

    public static function fromRenderedTemplate(RenderedTemplate $rendered): self
    {
        return new self(
            subject: $rendered->subject,
            body: $rendered->body,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'subject' => $this->subject,
            'body' => $this->body,
        ];
    }
}
