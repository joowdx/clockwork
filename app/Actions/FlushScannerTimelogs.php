<?php

namespace App\Actions;

use App\Events\TimelogsFlushed;
use App\Models\Scanner;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class FlushScannerTimelogs
{
    public function __invoke(Scanner $scanner, ?User $user = null, ?int $year = null, ?int $month = null): void
    {
        $user ??= Auth::user();

        $scanner->timelogs()
            ->withoutGlobalScopes()
            ->when($year, fn ($query) => $query->whereYear('time', $year))
            ->when($year && $month, fn ($query) => $query->whereMonth('time', $month))
            ->where(fn ($query) => $query->orWhere('pseudo', 1)->orWhere('recast', true)->orWhereHas('revision'))
            ->update(['shadow' => true]);

        $count = $scanner->timelogs()
            ->withoutGlobalScopes()
            ->when($year, fn ($query) => $query->whereYear('time', $year))
            ->when($year && $month, fn ($query) => $query->whereMonth('time', $month))
            ->where(fn ($query) => $query->orWhere('pseudo', 0)->orWhere('recast', false)->orWhereDoesntHave('revision'))
            ->delete();

        TimelogsFlushed::dispatch($scanner, $user, $count);
    }
}
