<?php

namespace App\Enums;

enum TimelogState: int
{
    case IN = 0;
    case OUT = 1;
    case OVERTIME_IN = 2;
    case OVERTIME_OUT = 3;
    case BREAK_IN = 4;
    case BREAK_OUT = 5;

    public function label(): string
    {
        return match($this) {
            self::IN => 'In',
            self::OUT => 'Out',
            self::OVERTIME_IN => 'Overtime In',
            self::OVERTIME_OUT => 'Overtime Out',
            self::BREAK_IN => 'Break In',
            self::BREAK_OUT => 'Break Out',
        };
    }
}
