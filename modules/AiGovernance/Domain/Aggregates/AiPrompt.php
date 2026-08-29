<?php

declare(strict_types=1);

namespace Modules\AiGovernance\Domain\Aggregates;

use DateTimeImmutable;
use Modules\AiGovernance\Domain\Enums\AiPromptStatus;
use Modules\AiGovernance\Domain\Exceptions\InvalidAiPromptTransition;
use Modules\AiGovernance\Domain\ValueObjects\AiPromptId;

final class AiPrompt
{
    private function __construct(
        private AiPromptId $id,
        private string $identifier,
        private string $purpose,
        private ?string $modelId,
        private int $version,
        private ?string $authorId,
        private string $content,
        private AiPromptStatus $status,
        private DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        AiPromptId $id,
        string $identifier,
        string $purpose,
        ?string $modelId,
        ?string $authorId,
        string $content,
        ?DateTimeImmutable $createdAt = null,
    ): self {
        return new self(
            $id,
            $identifier,
            $purpose,
            $modelId,
            1,
            $authorId,
            $content,
            AiPromptStatus::Draft,
            $createdAt ?? new DateTimeImmutable('now'),
        );
    }

    public static function restore(
        AiPromptId $id,
        string $identifier,
        string $purpose,
        ?string $modelId,
        int $version,
        ?string $authorId,
        string $content,
        AiPromptStatus $status,
        DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $identifier, $purpose, $modelId, $version, $authorId, $content, $status, $createdAt);
    }

    public function updateContent(string $content): void
    {
        if ($this->status === AiPromptStatus::Retired) {
            throw InvalidAiPromptTransition::create();
        }

        $this->content = $content;
        $this->version++;
    }

    public function approve(): void
    {
        if ($this->status !== AiPromptStatus::Draft) {
            throw InvalidAiPromptTransition::create();
        }

        $this->status = AiPromptStatus::Approved;
    }

    public function retire(): void
    {
        if ($this->status === AiPromptStatus::Retired) {
            throw InvalidAiPromptTransition::create();
        }

        $this->status = AiPromptStatus::Retired;
    }

    public function id(): AiPromptId
    {
        return $this->id;
    }

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function purpose(): string
    {
        return $this->purpose;
    }

    public function modelId(): ?string
    {
        return $this->modelId;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function authorId(): ?string
    {
        return $this->authorId;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function status(): AiPromptStatus
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
