<?php

namespace App\Enums;

enum UserRole: int
{
    case SYSTEM = 2;
    case USER = 0;
    case DEVELOPER = -1;
    case ADMINISTRATOR = 1;
    case DEPARTMENT_HEAD = 3;
    case SHIFT_MANAGER = 6;
    case SECURITY_PERSONNEL = 5;
    case ADMINISTRATIVE_OFFICER = 4;

    public function label(): string
    {
        return match ($this) {
            self::SYSTEM => 'System',
            self::USER => 'User',
            self::DEVELOPER, self::ADMINISTRATOR => 'Administrator',
            self::DEPARTMENT_HEAD => 'Department Head',
            self::SHIFT_MANAGER => 'Shift Manager',
            self::SECURITY_PERSONNEL => 'Security Personnel',
            self::ADMINISTRATIVE_OFFICER => 'Administrative Officer',
        };
    }
}
