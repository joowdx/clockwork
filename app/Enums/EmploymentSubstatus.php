<?php

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum EmploymentSubstatus: string implements HasDescription, HasLabel
{
    case JOB_ORDER = 'job-order';
    case CONTRACT_OF_SERVICE = 'contract-of-service';
    case NONE = '';

    public function getDescription(): string
    {
        return match ($this) {
            self::JOB_ORDER => 'Employee is hired on a per-job basis, typically for short-term or temporary work.',
            self::CONTRACT_OF_SERVICE => 'Employee is engaged in a contract of service, typically for short-term or temporary work.',
            default => null
        };
    }

    public function getLabel(): string
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
