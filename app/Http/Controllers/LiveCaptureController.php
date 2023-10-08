<?php

namespace App\Http\Controllers;

use App\Models\Scanner;
use App\Services\LiveCaptureService;

class LiveCaptureController extends Controller
{
    public function start(Scanner $scanner, LiveCaptureService $capture)
    {
        $capture->start($scanner);

        return redirect()->back();
    }
}
