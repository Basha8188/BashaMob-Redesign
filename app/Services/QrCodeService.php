<?php

namespace App\Services;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

class QrCodeService
{
    public function dataUri(string $data, int $size = 240): string
    {
        $qr = new QrCode(
            data: $data,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: 12,
            foregroundColor: new Color(33, 18, 56),
            backgroundColor: new Color(255, 255, 255),
        );

        return (new SvgWriter)->write($qr)->getDataUri();
    }
}
