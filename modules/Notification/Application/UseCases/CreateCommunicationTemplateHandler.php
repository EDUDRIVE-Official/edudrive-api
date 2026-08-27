<?php

declare(strict_types=1);

namespace Modules\Notification\Application\UseCases;

use Illuminate\Support\Str;
use Modules\Notification\Application\Commands\CreateCommunicationTemplateCommand;
use Modules\Notification\Application\Exceptions\CommunicationTemplateAlreadyExists;
use Modules\Notification\Application\Responses\CommunicationTemplateResponse;
use Modules\Notification\Domain\Aggregates\CommunicationTemplate;
use Modules\Notification\Domain\Repositories\CommunicationTemplateRepository;
use Modules\Notification\Domain\ValueObjects\CommunicationTemplateCode;
use Modules\Notification\Domain\ValueObjects\CommunicationTemplateId;

final readonly class CreateCommunicationTemplateHandler
{
    public function __construct(private CommunicationTemplateRepository $templates) {}

    public function handle(CreateCommunicationTemplateCommand $command): CommunicationTemplateResponse
    {
        $code = CommunicationTemplateCode::fromString($command->code);

        if ($this->templates->findByCodeAndLocale($code, $command->locale) !== null) {
            throw CommunicationTemplateAlreadyExists::create();
        }

        $template = CommunicationTemplate::create(
            id: CommunicationTemplateId::fromString((string) Str::uuid()),
            code: $code,
            locale: $command->locale,
            subjectTemplate: $command->subjectTemplate,
            bodyTemplate: $command->bodyTemplate,
            variables: $command->variables,
        );

        $this->templates->save($template);

        return CommunicationTemplateResponse::fromCommunicationTemplate($template);
    }
}
