<?php

declare(strict_types=1);

namespace Modules\Mobile\Infrastructure\Services;

use Modules\Mobile\Application\Services\MobilePushSender;
use Modules\Mobile\Domain\Aggregates\MobileDevice;
use Modules\Mobile\Domain\Repositories\MobileDeviceRepository;
use Modules\Mobile\Domain\ValueObjects\MobilePushMessage;
use Modules\Mobile\Infrastructure\Jobs\SendMobilePushJob;

final readonly class QueuedMobilePushSender implements MobilePushSender
{
    public function __construct(
        private MobileDeviceRepository $devices,
    ) {}

    public function send(MobilePushMessage $message): void
    {
        foreach ($this->devices->findWithPushTokenByUser($message->userId) as $device) {
            /** @var MobileDevice $device */
            $pushToken = $device->pushToken();
            if ($pushToken === null) {
                continue;
            }

            SendMobilePushJob::dispatch($pushToken, $message->title, $message->body);
        }
    }
}
