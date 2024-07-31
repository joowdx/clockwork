<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SuspensionType: string implements HasLabel
{
    case REGULAR_HOLIDAY = 'regular-holiday';
    case SPECIAL_HOLIDAY = 'special-holiday';
    case LOCAL_HOLIDAY = 'local-holiday';
    case WORK_SUSPENSION = 'work-suspension';

    public function getLabel(): ?string
    {
        return str($this->value)->title()->replace('-', ' ');
    }
}
