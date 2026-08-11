<?php

declare(strict_types=1);

namespace Modules\Academic\Application\Exceptions;

use Modules\Foundation\Domain\Exceptions\DomainException;

final class QuestionNotFound extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(
            message: sprintf('No existe una pregunta con el identificador %s.', $id),
            errorCode: 'QUESTION_NOT_FOUND',
            statusCode: 404,
        );
    }
}