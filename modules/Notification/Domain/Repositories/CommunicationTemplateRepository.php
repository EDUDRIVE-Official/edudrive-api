<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Repositories;

use Modules\Notification\Domain\Aggregates\CommunicationTemplate;
use Modules\Notification\Domain\ValueObjects\CommunicationTemplateCode;
use Modules\Notification\Domain\ValueObjects\CommunicationTemplateId;

interface CommunicationTemplateRepository
{
    public function save(CommunicationTemplate $template): void;

    public function findById(CommunicationTemplateId $id): ?CommunicationTemplate;

    public function findByCodeAndLocale(CommunicationTemplateCode $code, string $locale): ?CommunicationTemplate;

    /** @return list<CommunicationTemplate> */
    public function all(): array;
}
