<?php

namespace App\Actions;

use App\Events\TimelogsFlushed;
use App\Models\Scanner;
use App\Models\User;

class FlushScannerTimelogs
{
    public function __invoke(Scanner $scanner, ?User $user = null): void
    {
        $user ??= auth()->user();

        $scanner->timelogs()->where('pseudo', 1)->update(['shadow' => true]);

        $count = $scanner->timelogs()->where('pseudo', 0)->delete();

        TimelogsFlushed::dispatch($scanner, $user, $count);
    }
}
