<?php

declare(strict_types=1);

namespace Modules\Mobile\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SendMobilePushJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $pushToken,
        public readonly string $title,
        public readonly string $body,
    ) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(): void
    {
        Http::withHeaders([
            'Authorization' => 'key='.config('mobile.push_server_key'),
        ])
            ->timeout(5)
            ->post(
                (string) config('mobile.push_endpoint'),
                [
                    'to' => $this->pushToken,
                    'notification' => [
                        'title' => $this->title,
                        'body' => $this->body,
                    ],
                ],
            );
    }

    public function failed(?Throwable $exception): void
    {
        Log::warning('No se pudo enviar la notificacion push tras agotar los reintentos.', [
            'push_token' => $this->pushToken,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
