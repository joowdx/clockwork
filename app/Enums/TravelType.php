<?php

namespace App\Enums;

enum TravelType: int
{
    case LEAVE = 0;
    case MEMORANDUM_ORDER = 1;
    case PASS_SLIP = 2;
    case TRAVEL_ORDER = 3;
    case TRIP_TICKET = 4;

    public function label(): string
    {
        return match ($this) {
            self::LEAVE => 'Leave',
            self::MEMORANDUM_ORDER => 'Memorandum Order',
            self::PASS_SLIP => 'Pass Slip',
            self::TRAVEL_ORDER => 'Travel Order',
            self::TRIP_TICKET => 'Trip Ticket',
        };
    }
}
