<?php

declare(strict_types=1);

namespace Modules\Notification\Application\UseCases;

use Modules\Notification\Application\Exceptions\CommunicationTemplateNotFound;
use Modules\Notification\Application\Queries\PreviewCommunicationTemplateQuery;
use Modules\Notification\Application\Responses\RenderedTemplateResponse;
use Modules\Notification\Domain\Repositories\CommunicationTemplateRepository;
use Modules\Notification\Domain\ValueObjects\CommunicationTemplateId;

final readonly class PreviewCommunicationTemplateHandler
{
    public function __construct(private CommunicationTemplateRepository $templates) {}

    public function handle(PreviewCommunicationTemplateQuery $query): RenderedTemplateResponse
    {
        $template = $this->templates->findById(CommunicationTemplateId::fromString($query->templateId));
        if ($template === null) {
            throw CommunicationTemplateNotFound::withId($query->templateId);
        }

        return RenderedTemplateResponse::fromRenderedTemplate($template->render($query->variables));
    }
}
