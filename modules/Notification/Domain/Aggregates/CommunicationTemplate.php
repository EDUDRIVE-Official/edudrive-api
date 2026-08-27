<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Aggregates;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Notification\Domain\Enums\CommunicationTemplateStatus;
use Modules\Notification\Domain\Exceptions\InvalidCommunicationTemplateTransition;
use Modules\Notification\Domain\Exceptions\MissingTemplateVariable;
use Modules\Notification\Domain\ValueObjects\CommunicationTemplateCode;
use Modules\Notification\Domain\ValueObjects\CommunicationTemplateId;
use Modules\Notification\Domain\ValueObjects\RenderedTemplate;

final class CommunicationTemplate
{
    /** @param list<string> $variables */
    private function __construct(
        private CommunicationTemplateId $id,
        private CommunicationTemplateCode $code,
        private string $locale,
        private string $subjectTemplate,
        private string $bodyTemplate,
        private array $variables,
        private int $version,
        private CommunicationTemplateStatus $status,
        private DateTimeImmutable $registeredAt,
        private ?DateTimeImmutable $retiredAt,
        private ?string $retiredReason,
    ) {}

    /** @param list<string> $variables */
    public static function create(
        CommunicationTemplateId $id,
        CommunicationTemplateCode $code,
        string $locale,
        string $subjectTemplate,
        string $bodyTemplate,
        array $variables,
        ?DateTimeImmutable $registeredAt = null,
    ): self {
        self::guardLocale($locale);

        return new self(
            $id,
            $code,
            $locale,
            $subjectTemplate,
            $bodyTemplate,
            $variables,
            1,
            CommunicationTemplateStatus::Active,
            $registeredAt ?? new DateTimeImmutable('now'),
            null,
            null,
        );
    }

    /** @param list<string> $variables */
    public static function restore(
        CommunicationTemplateId $id,
        CommunicationTemplateCode $code,
        string $locale,
        string $subjectTemplate,
        string $bodyTemplate,
        array $variables,
        int $version,
        CommunicationTemplateStatus $status,
        DateTimeImmutable $registeredAt,
        ?DateTimeImmutable $retiredAt,
        ?string $retiredReason,
    ): self {
        self::guardLocale($locale);

        return new self($id, $code, $locale, $subjectTemplate, $bodyTemplate, $variables, $version, $status, $registeredAt, $retiredAt, $retiredReason);
    }

    /** @param list<string> $variables */
    public function updateContent(string $subjectTemplate, string $bodyTemplate, array $variables): void
    {
        if ($this->status === CommunicationTemplateStatus::Retired) {
            throw InvalidCommunicationTemplateTransition::cannotEditRetired();
        }

        $this->subjectTemplate = $subjectTemplate;
        $this->bodyTemplate = $bodyTemplate;
        $this->variables = $variables;
        $this->version++;
    }

    public function retire(?string $reason, DateTimeImmutable $at): void
    {
        if ($this->status === CommunicationTemplateStatus::Retired) {
            throw InvalidCommunicationTemplateTransition::alreadyRetired();
        }

        $this->status = CommunicationTemplateStatus::Retired;
        $this->retiredAt = $at;
        $this->retiredReason = $reason;
    }

    /** @param array<string, string> $values */
    public function render(array $values): RenderedTemplate
    {
        foreach ($this->variables as $variable) {
            if (! array_key_exists($variable, $values)) {
                throw MissingTemplateVariable::named($variable);
            }
        }

        $subject = $this->subjectTemplate;
        $body = $this->bodyTemplate;

        foreach ($values as $name => $value) {
            $placeholder = '{{'.$name.'}}';
            $subject = str_replace($placeholder, $value, $subject);
            $body = str_replace($placeholder, $value, $body);
        }

        return new RenderedTemplate($subject, $body);
    }

    public function id(): CommunicationTemplateId
    {
        return $this->id;
    }

    public function code(): CommunicationTemplateCode
    {
        return $this->code;
    }

    public function locale(): string
    {
        return $this->locale;
    }

    public function subjectTemplate(): string
    {
        return $this->subjectTemplate;
    }

    public function bodyTemplate(): string
    {
        return $this->bodyTemplate;
    }

    /** @return list<string> */
    public function variables(): array
    {
        return $this->variables;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function status(): CommunicationTemplateStatus
    {
        return $this->status;
    }

    public function registeredAt(): DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function retiredAt(): ?DateTimeImmutable
    {
        return $this->retiredAt;
    }

    public function retiredReason(): ?string
    {
        return $this->retiredReason;
    }

    private static function guardLocale(string $locale): void
    {
        if (preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $locale) !== 1) {
            throw new InvalidArgumentException('El idioma debe tener el formato ISO, por ejemplo "es" o "es-CR".');
        }
    }
}
