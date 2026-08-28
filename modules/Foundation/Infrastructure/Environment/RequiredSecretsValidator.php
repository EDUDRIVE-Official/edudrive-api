<?php

declare(strict_types=1);

namespace Modules\Foundation\Infrastructure\Environment;

use Modules\Foundation\Application\Exceptions\MissingRequiredSecrets;

final class RequiredSecretsValidator
{
    /**
     * @param  array<string, mixed>  $values
     */
    public function ensureAllPresent(array $values): void
    {
        $missing = [];

        foreach ($values as $name => $value) {
            if ($value === null || $value === '') {
                $missing[] = $name;
            }
        }

        if ($missing !== []) {
            throw MissingRequiredSecrets::forKeys($missing);
        }
    }
}
