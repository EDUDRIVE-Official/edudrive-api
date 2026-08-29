<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class NotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $notificationSubject,
        public readonly string $notificationBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->notificationSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: nl2br(e($this->notificationBody)),
        );
    }
}
