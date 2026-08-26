<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Commands;

use Modules\Academic\Domain\Entities\Responses\QuestionResponse;
use Modules\Foundation\Application\Commands\Command;

final readonly class AnswerAttemptQuestionCommand implements Command
{
    public function __construct(
        public string $attemptId,
        public string $userId,
        public int $position,
        public QuestionResponse $response,
    ) {}
}
