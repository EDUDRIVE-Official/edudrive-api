<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use Illuminate\Support\Facades\Log;
use Modules\Academic\Application\Exceptions\CoursePedagogicalQualityRequired;
use Modules\Academic\Application\Services\CoursePublicationQualityGate;
use Modules\Academic\Domain\ValueObjects\CourseId;

final class DemoCoursePublication
{
    public static function isReady(string $courseId): bool
    {
        if (! app()->environment(['local', 'testing'])) {
            return false;
        }

        try {
            app(CoursePublicationQualityGate::class)->assertReady(CourseId::fromString($courseId));
        } catch (CoursePedagogicalQualityRequired $exception) {
            Log::notice('Curso de demostración conservado en borrador.', [
                'course_id' => $courseId,
                'reason' => $exception->getMessage(),
            ]);

            return false;
        }

        return true;
    }
}
