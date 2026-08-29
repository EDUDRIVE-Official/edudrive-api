<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use Illuminate\Support\Str;
use Modules\AiGovernance\Application\Commands\CreateAiPromptCommand;
use Modules\AiGovernance\Application\Responses\AiPromptResponse;
use Modules\AiGovernance\Domain\Aggregates\AiPrompt;
use Modules\AiGovernance\Domain\Repositories\AiPromptRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiPromptId;

final readonly class CreateAiPromptHandler
{
    public function __construct(private AiPromptRepository $prompts) {}

    public function handle(CreateAiPromptCommand $command): AiPromptResponse
    {
        $prompt = AiPrompt::create(
            id: AiPromptId::fromString((string) Str::uuid()),
            identifier: $command->identifier,
            purpose: $command->purpose,
            modelId: $command->modelId,
            authorId: $command->authorId,
            content: $command->content,
        );

        $this->prompts->save($prompt);

        return AiPromptResponse::fromPrompt($prompt);
    }
}
