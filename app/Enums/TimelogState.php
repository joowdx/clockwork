<?php

namespace App\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TimelogState: int implements HasIcon, HasLabel
{
    case UNKNOWN = -1;
    case CHECK_IN = 0;
    case CHECK_OUT = 1;
    case BREAK_OUT = 2;
    case BREAK_IN = 3;
    case OVERTIME_IN = 4;
    case OVERTIME_OUT = 5;
    case CHECK_IN_PM = 6;
    case CHECK_OUT_PM = 7;

    public static function login(): array
    {
        return collect(self::cases())->filter->in()->toArray();
    }

    public static function logout(): array
    {
        return collect(self::cases())->filter->out()->toArray();
    }

    public function in(): bool
    {
        return in_array($this, [
            self::CHECK_IN,
            self::BREAK_IN,
            self::OVERTIME_IN,
            self::CHECK_IN_PM,
        ]);
    }

    public function out(): bool
    {
        return in_array($this, [
            self::CHECK_OUT,
            self::BREAK_OUT,
            self::OVERTIME_OUT,
            self::CHECK_OUT_PM,
        ]);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::CHECK_IN => 'Check In',
            self::CHECK_OUT => 'Check Out',
            self::BREAK_OUT => 'Break Out',
            self::BREAK_IN => 'Break In',
            self::OVERTIME_IN => 'Overtime In',
            self::OVERTIME_OUT => 'Overtime Out',
            self::CHECK_IN_PM => 'Check In PM',
            self::CHECK_OUT_PM => 'Check Out PM',
            default => 'Unknown State',
        };
    }

    public function getIcon(): ?string
    {
        if (! $this->in() && ! $this->out()) {
            return 'heroicon-m-no-symbol';
        }

        return $this->in()
            ? 'gmdi-login-o'
            : 'gmdi-logout-o';
    }
}
