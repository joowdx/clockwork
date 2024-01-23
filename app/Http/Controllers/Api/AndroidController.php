<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MobileApplicationDownloader;

class AndroidController extends Controller
{
    public function __invoke(MobileApplicationDownloader $helper): mixed
    {
        return [
            'url' => $helper->link(),
            'version' =>$helper->version(),
        ];
    }
}
