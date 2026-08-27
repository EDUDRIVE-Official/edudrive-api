<?php

declare(strict_types=1);

namespace Modules\Notification\Application\UseCases;

use Modules\Notification\Application\Queries\ListCommunicationTemplatesQuery;
use Modules\Notification\Application\Responses\CommunicationTemplateResponse;
use Modules\Notification\Domain\Aggregates\CommunicationTemplate;
use Modules\Notification\Domain\Repositories\CommunicationTemplateRepository;

final readonly class ListCommunicationTemplatesHandler
{
    public function __construct(private CommunicationTemplateRepository $templates) {}

    /** @return list<CommunicationTemplateResponse> */
    public function handle(ListCommunicationTemplatesQuery $query): array
    {
        return array_map(
            static fn (CommunicationTemplate $template): CommunicationTemplateResponse => CommunicationTemplateResponse::fromCommunicationTemplate($template),
            $this->templates->all(),
        );
    }
}
