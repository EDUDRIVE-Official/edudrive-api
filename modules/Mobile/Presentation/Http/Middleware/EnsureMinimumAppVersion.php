<?php

declare(strict_types=1);

namespace Modules\Mobile\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Admin\Domain\Repositories\SystemSettingRepository;
use Modules\Admin\Domain\ValueObjects\SystemSettingKey;
use Modules\Foundation\Presentation\Http\Responses\ApiErrorResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureMinimumAppVersion
{
    private const string SETTING_KEY = 'mobile_min_app_version';

    public function __construct(
        private SystemSettingRepository $settings,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $minVersion = $this->settings->findByKey(SystemSettingKey::fromString(self::SETTING_KEY))?->value();

        if ($minVersion === null) {
            return $next($request);
        }

        $appVersion = $request->header('X-App-Version');

        if ($appVersion === null) {
            return ApiErrorResponse::make(
                message: 'Debe enviar el header X-App-Version.',
                status: 400,
                code: 'MISSING_APP_VERSION',
            );
        }

        if (version_compare((string) $appVersion, $minVersion, '<')) {
            return ApiErrorResponse::make(
                message: "La versión {$appVersion} ya no es compatible. Actualice a la versión {$minVersion} o superior.",
                status: 426,
                code: 'APP_VERSION_UNSUPPORTED',
            );
        }

        return $next($request);
    }
}
