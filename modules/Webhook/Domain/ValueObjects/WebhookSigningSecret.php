<?php

declare(strict_types=1);

namespace Modules\Webhook\Domain\ValueObjects;

final readonly class WebhookSigningSecret
{
    private function __construct(
        private string $value,
    ) {}

    public static function generate(): self
    {
        return new self(bin2hex(random_bytes(32)));
    }

    public static function fromPlainValue(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->value);
    }
}
