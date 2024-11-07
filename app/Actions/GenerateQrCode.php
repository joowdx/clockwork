<?php

namespace App\Actions;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GenerateQrCode
{
    public function __invoke(mixed $data, int $size = 256, ?string $image = null, string $format = 'svg'): string
    {
        return $this->generate($data, $size, $image, $format);
    }

    public function generate(mixed $data, int $size = 256, ?string $image = null, string $format = 'svg'): string
    {
        $from = [19, 91, 146];

        $to = [0, 254, 255];

        $qr = QrCode::size($size)
            ->format($format)
            ->margin(1)
            ->gradient($from[0], $from[1], $from[2], $to[0], $to[1], $to[2], 'diagonal')
            ->eye('circle')
            ->style('dot')
            ->errorCorrection('M');

        if ($image) {
            $qr->merge($image, .3, true);
        }

        return $qr->generate($data);
    }
}
