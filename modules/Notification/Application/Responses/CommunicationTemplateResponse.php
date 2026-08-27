<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Responses;

use DateTimeInterface;
use Modules\Notification\Domain\Aggregates\CommunicationTemplate;

final readonly class CommunicationTemplateResponse
{
    /** @param list<string> $variables */
    public function __construct(
        public string $id,
        public string $code,
        public string $locale,
        public string $subjectTemplate,
        public string $bodyTemplate,
        public array $variables,
        public int $version,
        public string $status,
        public string $registeredAt,
        public ?string $retiredAt,
        public ?string $retiredReason,
    ) {}

    public static function fromCommunicationTemplate(CommunicationTemplate $template): self
    {
        return new self(
            id: $template->id()->value(),
            code: $template->code()->value(),
            locale: $template->locale(),
            subjectTemplate: $template->subjectTemplate(),
            bodyTemplate: $template->bodyTemplate(),
            variables: $template->variables(),
            version: $template->version(),
            status: $template->status()->value,
            registeredAt: $template->registeredAt()->format(DateTimeInterface::ATOM),
            retiredAt: $template->retiredAt()?->format(DateTimeInterface::ATOM),
            retiredReason: $template->retiredReason(),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'locale' => $this->locale,
            'subject_template' => $this->subjectTemplate,
            'body_template' => $this->bodyTemplate,
            'variables' => $this->variables,
            'version' => $this->version,
            'status' => $this->status,
            'registered_at' => $this->registeredAt,
            'retired_at' => $this->retiredAt,
            'retired_reason' => $this->retiredReason,
        ];
    }
}
