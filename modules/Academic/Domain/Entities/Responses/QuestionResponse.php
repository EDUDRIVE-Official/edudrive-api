<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities\Responses;

/** Forma canónica serializable de la respuesta correcta de una pregunta. */
interface QuestionResponse
{
    /** @return array<string, mixed> */
    public function toArray(): array;

    public function matches(self $other): bool;
}
