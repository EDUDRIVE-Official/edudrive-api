<?php

declare(strict_types=1);

namespace Modules\Notification\Application\UseCases;

use Modules\Notification\Application\Exceptions\CommunicationTemplateNotFound;
use Modules\Notification\Application\Queries\GetCommunicationTemplateQuery;
use Modules\Notification\Application\Responses\CommunicationTemplateResponse;
use Modules\Notification\Domain\Repositories\CommunicationTemplateRepository;
use Modules\Notification\Domain\ValueObjects\CommunicationTemplateId;

final readonly class GetCommunicationTemplateHandler
{
    public function __construct(private CommunicationTemplateRepository $templates) {}

    public function handle(GetCommunicationTemplateQuery $query): CommunicationTemplateResponse
    {
        $template = $this->templates->findById(CommunicationTemplateId::fromString($query->templateId));
        if ($template === null) {
            throw CommunicationTemplateNotFound::withId($query->templateId);
        }

        return CommunicationTemplateResponse::fromCommunicationTemplate($template);
    }
}
