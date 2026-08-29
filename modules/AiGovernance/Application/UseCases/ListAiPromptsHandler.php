<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\UseCases;

use Modules\AiGovernance\Application\Queries\ListAiPromptsQuery;
use Modules\AiGovernance\Application\Responses\AiPromptResponse;
use Modules\AiGovernance\Domain\Aggregates\AiPrompt;
use Modules\AiGovernance\Domain\Repositories\AiPromptRepository;

final readonly class ListAiPromptsHandler
{
    public function __construct(private AiPromptRepository $prompts) {}

    /** @return list<AiPromptResponse> */
    public function handle(ListAiPromptsQuery $query): array
    {
        return array_map(
            static fn (AiPrompt $prompt): AiPromptResponse => AiPromptResponse::fromPrompt($prompt),
            $this->prompts->all(),
        );
    }
}
