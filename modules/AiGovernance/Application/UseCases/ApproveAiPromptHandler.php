<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use Modules\AiGovernance\Application\Commands\ApproveAiPromptCommand;
use Modules\AiGovernance\Application\Exceptions\AiPromptNotFound;
use Modules\AiGovernance\Application\Responses\AiPromptResponse;
use Modules\AiGovernance\Domain\Repositories\AiPromptRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiPromptId;

final readonly class ApproveAiPromptHandler
{
    public function __construct(private AiPromptRepository $prompts) {}

    public function handle(ApproveAiPromptCommand $command): AiPromptResponse
    {
        $prompt = $this->prompts->findById(AiPromptId::fromString($command->promptId));
        if ($prompt === null) {
            throw AiPromptNotFound::withId($command->promptId);
        }

        $prompt->approve();
        $this->prompts->save($prompt);

        return AiPromptResponse::fromPrompt($prompt);
    }
}
