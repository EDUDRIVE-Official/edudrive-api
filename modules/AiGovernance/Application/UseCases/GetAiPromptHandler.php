<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use Modules\AiGovernance\Application\Exceptions\AiPromptNotFound;
use Modules\AiGovernance\Application\Queries\GetAiPromptQuery;
use Modules\AiGovernance\Application\Responses\AiPromptResponse;
use Modules\AiGovernance\Domain\Repositories\AiPromptRepository;
use Modules\AiGovernance\Domain\ValueObjects\AiPromptId;

final readonly class GetAiPromptHandler
{
    public function __construct(private AiPromptRepository $prompts) {}

    public function handle(GetAiPromptQuery $query): AiPromptResponse
    {
        $prompt = $this->prompts->findById(AiPromptId::fromString($query->promptId));
        if ($prompt === null) {
            throw AiPromptNotFound::withId($query->promptId);
        }

        return AiPromptResponse::fromPrompt($prompt);
    }
}
