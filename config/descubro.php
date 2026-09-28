<?php

declare(strict_types=1);

return [
    // Separate switches for staging and restricted production review.
    'staging_enabled' => (bool) env('DESCUBRO_STAGING_ENABLED', false),
    'production_review_enabled' => (bool) env('DESCUBRO_PRODUCTION_REVIEW_ENABLED', false),
];
