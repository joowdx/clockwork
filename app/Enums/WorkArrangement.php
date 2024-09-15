<?php

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum WorkArrangement: string implements HasDescription, HasLabel
{
    case STANDARD_WORK_HOUR = 'standard-work-hour';
    case FLEXI_TIME = 'flexi-time';
    case WORK_SHIFTING = 'work-shifting';
    // case COMPRESSED_WORK_WEEK = 'compressed-work-week';
    case ROUND_THE_CLOCK = 'round-the-clock';

    // case UNSET = '';

    public function getLabel(): ?string
    {
        return match ($this) {
            // self::UNSET => 'Unset Arrangement',
            default => str($this->name)->title()->headline(),
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::STANDARD_WORK_HOUR => 'Normal working hours',
            self::WORK_SHIFTING => 'Employees work different shifts without set breaks',
            self::FLEXI_TIME => 'Custom start and end times within set limits',
            // self::COMPRESSED_WORK_WEEK => 'More hours in fewer days',
            self::ROUND_THE_CLOCK => 'For continuous operation 24h or 48h shifts',
            default => null,
        };
    }
}
