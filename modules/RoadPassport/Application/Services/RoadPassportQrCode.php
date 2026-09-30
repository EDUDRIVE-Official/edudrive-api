<?php

declare(strict_types=1);

namespace Modules\RoadPassport\Application\Services;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;

final class RoadPassportQrCode
{
    public function dataUri(string $verificationUrl): string
    {
        $result = (new SvgWriter)->write(new QrCode(
            data: $verificationUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 280,
            margin: 12,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        ));

        return $result->getDataUri();
    }
}
