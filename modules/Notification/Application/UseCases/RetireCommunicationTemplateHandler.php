<?php

declare(strict_types=1);

namespace Modules\Notification\Application\UseCases;

use DateTimeImmutable;
use Modules\Notification\Application\Commands\RetireCommunicationTemplateCommand;
use Modules\Notification\Application\Exceptions\CommunicationTemplateNotFound;
use Modules\Notification\Application\Responses\CommunicationTemplateResponse;
use Modules\Notification\Domain\Repositories\CommunicationTemplateRepository;
use Modules\Notification\Domain\ValueObjects\CommunicationTemplateId;

final readonly class RetireCommunicationTemplateHandler
{
    public function __construct(private CommunicationTemplateRepository $templates) {}

    public function handle(RetireCommunicationTemplateCommand $command): CommunicationTemplateResponse
    {
        $template = $this->templates->findById(CommunicationTemplateId::fromString($command->templateId));
        if ($template === null) {
            throw CommunicationTemplateNotFound::withId($command->templateId);
        }

        $template->retire($command->reason, new DateTimeImmutable('now'));
        $this->templates->save($template);

        return CommunicationTemplateResponse::fromCommunicationTemplate($template);
    }
}
