<?php

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum EmploymentStatus: string implements HasDescription, HasLabel
{
    case PERMANENT = 'permanent';
    // case TEMPORARY = 'temporary';
    // case COTERMINOUS = 'coterminous';
    // case FIXED_TERM = 'fixed-term';
    case CONTRACTUAL = 'contractual';
    // case CASUAL = 'casual';
    // case SUBSTITUTE = 'substitute';
    // case PROVISIONAL = 'provisional';
    case INTERNSHIP = 'internship';
    case NONE = '';

    public function getDescription(): ?string
    {
        return match ($this) {
            self::PERMANENT => 'Employee has a stable, long-term employment relationship with the organization.',
            // self::TEMPORARY => 'Employee is hired for a short-term duration or for a specific project or task.',
            // self::COTERMINOUS => 'Employee\'s employment is tied to the term of a specific official or elected official.',
            // self::FIXED_TERM => 'Employee is hired for a fixed term or duration.',
            self::CONTRACTUAL => 'Employee is hired based on a specific contract or agreement, which may be for a fixed term or duration.',
            // self::CASUAL => 'Employee is hired on an as-needed basis, typically for short-term or temporary work.',
            // self::SUBSTITUTE => 'Employee serves as a temporary replacement for another employee who is absent.',
            // self::PROVISIONAL => 'Employee is appointed temporarily pending the completion of certain requirements or procedures.',
            self::INTERNSHIP => 'Employee is engaged in an internship or training program to gain practical experience in a specific field or industry.',
            default => null
        };
    }

    public function getLabel(): ?string
    {
        if ($this === self::NONE) {
            return 'None';
        }

        return str($this->value)->replace('-', ' ')->title();
    }

    public static function keyValuePair()
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
            ->filter();
    }
}
