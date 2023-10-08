<?php

namespace App\Services;

use App\Jobs\RunLiveCapture;
use App\Models\Scanner;

class LiveCaptureService
{
    public function start(Scanner $scanner)
    {
        RunLiveCapture::dispatch($scanner);
    }
}
