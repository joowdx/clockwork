<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class MobileApplicationDownloader
{
    public function link()
    {
        return collect(File::allFiles(public_path("android")))
            ->map->getFilename()
            ->filter(fn ($file) => str_starts_with($file, 'clockwork'))
            ->map(fn ($file) => url("android/{$file}"))
            ->sortDesc()
            ->first();
    }

    public function version()
    {
        return substr($version = explode('-v', $this->link())[1] ?? '', 0, strpos($version, '.apk'));
    }
}
