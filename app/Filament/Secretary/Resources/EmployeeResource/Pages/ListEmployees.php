<?php

namespace App\Filament\Secretary\Resources\EmployeeResource\Pages;

use App\Filament\Secretary\Resources\EmployeeResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    public function getSubheading(): string|Htmlable|null
    {
        $subheading = <<<'HTML'
            <span class="text-sm text-custom-600 dark:text-custom-400" style="--c-400:var(--warning-400);--c-600:var(--warning-600);">
                This only lists employees who are either deployed or enrolled in your assigned offices or scanners.
            </span>
        HTML;

        return str($subheading)->toHtmlString();
    }
}
