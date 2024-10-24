<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TimesheetPeriod: string implements HasLabel
{
    case FIRST = '1st';
    case SECOND = '2nd';
    case FULL = 'full';

    public function getLabel(): string
    {
        return match ($this) {
            self::FULL => 'Full Month',
            default => "{$this->value} Half",
        };
    }
}
