<?php

namespace App\Enums;

enum UserType: int
{
    case DEVELOPER = -1;
    case USER = 0;
    case ADMINISTRATOR = 1;
    case SYSTEM = 2;
    case DEPARTMENT_HEAD = 3;

    public function label(): string
    {
        return match ($this) {
            self::USER => 'User',
            self::SYSTEM => 'System',
            self::DEVELOPER, self::ADMINISTRATOR => 'Administrator',
            self::DEPARTMENT_HEAD => 'Department Head',
        };
    }
}
