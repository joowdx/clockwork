<?php

namespace App\Filament\Superuser\Resources\ActivityResource\Pages;

use App\Filament\Superuser\Resources\ActivityResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        $upcoming = <<<'HTML'
            <span class="text-sm text-custom-600 dark:text-custom-400" style="--c-400:var(--warning-400);--c-600:var(--warning-600);">
            Please note that this list is not exhaustive.
            </span>
        HTML;

        return str($upcoming)->toHtmlString();
    }
}
