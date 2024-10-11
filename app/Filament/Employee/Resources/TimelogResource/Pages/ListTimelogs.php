<?php

namespace App\Filament\Employee\Resources\TimelogResource\Pages;

use App\Filament\Employee\Resources\TimelogResource;
use App\Filament\Employee\Widgets\ScannerStatisticsWidget;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListTimelogs extends ListRecords
{
    protected static string $resource = TimelogResource::class;

    public function getBreadcrumb(): ?string
    {
        return Filament::auth()->user()->titled_name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        $warning = <<<'HTML'
            <span class="text-sm text-custom-600 dark:text-custom-400" style="--c-400:var(--warning-400);--c-600:var(--warning-600);">
                Since this system is still in active development, please always secure a backup of your data by downloading and storing it in a safe place.
            </span>
        HTML;

        return str($warning)->toHtmlString();
    }

    protected function getFooterWidgets(): array
    {
        return [
            ScannerStatisticsWidget::class,
        ];
    }
}
