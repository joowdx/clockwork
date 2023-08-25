<?php

namespace App\Enums;

enum UserType: int
{
    case DEVELOPER = -1;
    case USER = 0;
    case ADMINISTRATOR = 1;
    case SYSTEM = 2;
    case DEPARTMENT_HEAD = 3;

    public function label (): string
    {
        return match($this) {
            static::USER => 'User',
            static::SYSTEM => 'System',
            static::DEVELOPER, static::ADMINISTRATOR => 'Administrator',
            static::DEPARTMENT_HEAD => 'Department Head',
        };
    }
}
