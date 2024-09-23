<?php

namespace App\Enums;

use Filament\Support\Contracts\HasIcon;

enum ExportStatus: string implements HasIcon
{
    case READY = 'ready';
    case PROCESSING = 'processing';
    case DOWNLOADED = 'downloaded';
    case FAILED = 'failed';

    public function getIcon(): string
    {
        return match ($this) {
            self::READY => 'heroicon-o-clipboard-document-check',
            self::PROCESSING => 'heroicon-o-refresh',
            self::DOWNLOADED => 'heroicon-o-folder-arrow-down',
            self::FAILED => 'heroicon-o-archive-box-x-mark',
        };
    }
}
