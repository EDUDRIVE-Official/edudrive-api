<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class CreateQuestionCommand implements Command
{
    /**
     * @param  array<string, mixed>  $response
     * @param  list<array{refId: string, label: string, side?: string|null}>  $options
     * @param  list<array{type: string, url: string}>  $media
     */
    public function __construct(
        public string $competencyId,
        public string $type,
        public string $prompt,
        public int $score,
        public array $response,
        public array $options = [],
        public ?string $explanation = null,
        public array $media = [],
    ) {}
}
