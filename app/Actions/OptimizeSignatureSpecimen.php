<?php

namespace App\Actions;

use Imagick;

class OptimizeSignatureSpecimen
{
    const MAX = 480;

    public function __invoke(string $path): mixed
    {
        $image = new Imagick;

        $image->{file_exists($path) ? 'readImage' : 'readImageBlob'}($path);

        $width = $image->getImageWidth();

        $height = $image->getImageHeight();

        $image->setImageFormat('webp');

        $image->resizeImage($width > $height ? self::MAX : 0, $width > $height ? 0 : self::MAX, Imagick::FILTER_LANCZOS, true);

        $image->stripImage();

        $image->setImageDepth(1);

        $image->setImageCompressionQuality(50);

        try {
            if (file_exists($path)) {
                $image->writeImage(pathinfo($path, PATHINFO_DIRNAME).'/'.pathinfo($path, PATHINFO_FILENAME).'.webp');
            } else {
                return [
                    'content' => $image->getImageBlob(),
                    'mime' => $image->getImageMimeType(),
                ];
            }
        } finally {
            $image->clear();
        }
    }
}
