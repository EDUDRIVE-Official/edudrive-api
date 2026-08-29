<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Application\Responses;

use DateTimeInterface;
use Modules\AiGovernance\Domain\Aggregates\AiPrompt;

final readonly class AiPromptResponse
{
    public function __construct(
        public string $id,
        public string $identifier,
        public string $purpose,
        public ?string $modelId,
        public int $version,
        public ?string $authorId,
        public string $content,
        public string $status,
        public string $createdAt,
    ) {}

    public static function fromPrompt(AiPrompt $prompt): self
    {
        return new self(
            id: $prompt->id()->value(),
            identifier: $prompt->identifier(),
            purpose: $prompt->purpose(),
            modelId: $prompt->modelId(),
            version: $prompt->version(),
            authorId: $prompt->authorId(),
            content: $prompt->content(),
            status: $prompt->status()->value,
            createdAt: $prompt->createdAt()->format(DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'identifier' => $this->identifier,
            'purpose' => $this->purpose,
            'model_id' => $this->modelId,
            'version' => $this->version,
            'author_id' => $this->authorId,
            'content' => $this->content,
            'status' => $this->status,
            'created_at' => $this->createdAt,
        ];
    }
}
