<?php

namespace App\Actions;

use Imagick;
use ImagickException;

class OptimizeImage
{
    const MAX = 480;

    /**
     * @throws ImagickException
     */
    public function __invoke(string $path): array|string|null
    {
        return $this->optimize($path);
    }

    /**
     * @throws ImagickException
     */
    public function optimize(string $path): array|string|null
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
                $optimized = pathinfo($path, PATHINFO_DIRNAME).'/'.pathinfo($path, PATHINFO_FILENAME).'.webp';

                if ($path !== $optimized) {
                    unlink($path);
                }

                $image->writeImage($optimized);

                return $optimized;
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
