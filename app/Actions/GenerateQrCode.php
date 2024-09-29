<?php

namespace App\Actions;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class GenerateQrCode
{
    public function __invoke(mixed $data, int $size = 256): string
    {
        return $this->generate($data, $size);
    }

    public function generate(mixed $data, int $size = 256): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, 0, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(0, 0, 0))),
            new SvgImageBackEnd,
        );

        return (new Writer($renderer))->writeString($data);
    }
}
