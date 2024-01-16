<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AndroidController extends Controller
{
    public function __invoke(): mixed
    {
        return [
            'url' => $file = collect(File::allFiles('/var/www/html/public/android'))
                ->map
                ->getFilename()
                ->filter(fn ($file) => str_starts_with($file, 'clockwork'))
                ->map(fn ($file) => "https://clockwork.davaodelsur.gov.ph/android/$file")
                ->first(),
            'version' => substr($version = explode('-v', $file)[1] ?? '', 0, strpos($version, '.apk')),
        ];
    }
}
