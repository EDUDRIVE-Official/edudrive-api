<?php

declare(strict_types=1);

namespace Modules\Notification\Application\UseCases;

use Modules\Notification\Application\Commands\UpdateCommunicationTemplateCommand;
use Modules\Notification\Application\Exceptions\CommunicationTemplateNotFound;
use Modules\Notification\Application\Responses\CommunicationTemplateResponse;
use Modules\Notification\Domain\Repositories\CommunicationTemplateRepository;
use Modules\Notification\Domain\ValueObjects\CommunicationTemplateId;

final readonly class UpdateCommunicationTemplateHandler
{
    public function __construct(private CommunicationTemplateRepository $templates) {}

    public function handle(UpdateCommunicationTemplateCommand $command): CommunicationTemplateResponse
    {
        $template = $this->templates->findById(CommunicationTemplateId::fromString($command->templateId));
        if ($template === null) {
            throw CommunicationTemplateNotFound::withId($command->templateId);
        }

        $template->updateContent(
            subjectTemplate: $command->subjectTemplate,
            bodyTemplate: $command->bodyTemplate,
            variables: $command->variables,
        );

        $this->templates->save($template);

        return CommunicationTemplateResponse::fromCommunicationTemplate($template);
    }
}
