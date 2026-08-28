<?php

declare(strict_types=1);

namespace Modules\Foundation\Infrastructure\Security;

final class SecretPatternScanner
{
    /**
     * @var array<string, string>
     */
    private const PATTERNS = [
        'AWS Access Key ID' => '/AKIA[0-9A-Z]{16}/',
        'AWS Secret Access Key' => '/(?i)aws_secret_access_key\s*[:=]\s*[\'"]?[A-Za-z0-9\/+=]{40}[\'"]?/',
        'Bloque de llave privada' => '/-----BEGIN (RSA |EC |OPENSSH |DSA )?PRIVATE KEY-----/',
        'Webhook de Slack' => '#https://hooks\.slack\.com/services/T[0-9A-Za-z]+/B[0-9A-Za-z]+/[0-9A-Za-z]+#',
    ];

    /**
     * @return list<string>
     */
    public function scan(string $line): array
    {
        $matches = [];

        foreach (self::PATTERNS as $label => $pattern) {
            if (preg_match($pattern, $line) === 1) {
                $matches[] = $label;
            }
        }

        return $matches;
    }
}
