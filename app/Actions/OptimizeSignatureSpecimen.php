<?php

namespace App\Actions;

use Imagick;

class OptimizeSignatureSpecimen
{
    const MAX_SIZE = 500 * 1024;

    public function __invoke(string $path)
    {
        $image = imagecreatefromstring($path);

        imagepng($image, storage_path('signing/test.png'), 1);
    }
}
