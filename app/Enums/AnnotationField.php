<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AnnotationField: string implements HasLabel
{
    case DATE = 'px';
    case RANGE = 'py';
    case ARRIVAL_1 = 'p1';
    case DEPARTURE_1 = 'p2';
    case ARRIVAL_2 = 'p3';
    case DEPARTURE_2 = 'p4';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DATE => 'Entire Date',
            self::RANGE => 'Date Range',
            self::ARRIVAL_1 => 'Arrival 1',
            self::DEPARTURE_1 => 'Departure 1',
            self::ARRIVAL_2 => 'Arrival 2',
            self::DEPARTURE_2 => 'Departure 2',
        };
    }
}
