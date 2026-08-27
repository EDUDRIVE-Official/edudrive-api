<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Commands;

use Modules\Foundation\Application\Commands\Command;

final readonly class UpdateNotificationPreferenceCommand implements Command
{
    /**
     * @param  list<string>  $allowedChannels
     * @param  list<string>  $mutedCategories
     */
    public function __construct(
        public string $userId,
        public array $allowedChannels,
        public array $mutedCategories,
        public string $frequency,
        public ?string $quietHoursStart,
        public ?string $quietHoursEnd,
    ) {}
}
