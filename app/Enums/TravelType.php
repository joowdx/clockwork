<?php

namespace App\Enums;

enum TravelType: int
{
    case LEAVE = 0;
    case MEMORANDUM_ORDER = 1;
    case PASS_SLIP = 2;
    case TRAVEL_ORDER = 3;
    case TRIP_TICKET = 4;

    public function label (): string
    {
        return match($this) {
            static::LEAVE => 'Leave',
            static::MEMORANDUM_ORDER => 'Memorandum Order',
            static::PASS_SLIP => 'Pass Slip',
            static::TRAVEL_ORDER => 'Travel Order',
            static::TRIP_TICKET => 'Trip Ticket',
        };
    }
}
