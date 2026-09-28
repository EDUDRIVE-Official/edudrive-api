<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Application\Services;

final readonly class RoadPassportVerificationCode
{
    public function __construct(private string $secret) {}

    public function issue(string $passportId, int $version = 1): string
    {
        $payload = strtolower($passportId).'.'.$version;

        return $payload.'.'.$this->signature($payload);
    }

    /** @return array{id: string, version: int}|null */
    public function payloadFrom(string $code): ?array
    {
        $parts = explode('.', strtolower(trim($code)), 3);
        if (count($parts) !== 3
            || ! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $parts[0])
            || ! ctype_digit($parts[1])
            || (int) $parts[1] < 1) {
            return null;
        }

        $payload = $parts[0].'.'.$parts[1];

        return hash_equals($this->signature($payload), $parts[2])
            ? ['id' => $parts[0], 'version' => (int) $parts[1]]
            : null;
    }

    private function signature(string $passportId): string
    {
        return hash_hmac('sha256', 'road-passport|'.strtolower($passportId), $this->secret);
    }
}
