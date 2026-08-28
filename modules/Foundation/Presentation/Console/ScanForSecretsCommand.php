<?php

declare(strict_types=1);

namespace Modules\Foundation\Presentation\Console;

use Illuminate\Console\Command;
use Modules\Foundation\Infrastructure\Security\SecretPatternScanner;

final class ScanForSecretsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'secrets:scan';

    /**
     * @var string
     */
    protected $description = 'Escanea el contenido recibido por STDIN en busca de patrones conocidos de secretos (uso principal: hook de pre-commit).';

    public function handle(SecretPatternScanner $scanner): int
    {
        $contents = stream_get_contents(STDIN);
        $lines = $contents === false || $contents === '' ? [] : explode("\n", $contents);

        $violations = $this->findViolations($scanner, $lines);

        foreach ($violations as [$lineNumber, $label]) {
            $this->error(sprintf('Línea %d: posible secreto detectado (%s)', $lineNumber, $label));
        }

        if ($violations !== []) {
            $this->error('Se detectaron posibles secretos. Revisa el contenido antes de continuar.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $lines
     * @return list<array{0: int, 1: string}>
     */
    public function findViolations(SecretPatternScanner $scanner, array $lines): array
    {
        $violations = [];

        foreach ($lines as $index => $line) {
            foreach ($scanner->scan($line) as $label) {
                $violations[] = [$index + 1, $label];
            }
        }

        return $violations;
    }
}
