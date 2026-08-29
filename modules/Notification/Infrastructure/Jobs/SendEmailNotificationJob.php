<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Identity\Domain\Repositories\UserRepository;
use Modules\Notification\Infrastructure\Mail\NotificationMail;
use Throwable;

final class SendEmailNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $userId,
        public readonly string $subject,
        public readonly string $body,
    ) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(UserRepository $users): void
    {
        $user = $users->findById($this->userId);
        if ($user === null) {
            return;
        }

        Mail::to($user->email()->value())->send(new NotificationMail($this->subject, $this->body));
    }

    public function failed(?Throwable $exception): void
    {
        Log::warning('No se pudo enviar el correo de notificacion tras agotar los reintentos.', [
            'user_id' => $this->userId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
