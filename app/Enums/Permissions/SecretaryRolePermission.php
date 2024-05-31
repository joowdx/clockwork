<?php

namespace App\Enums\Permissions;

use Filament\Support\Contracts\HasLabel;

enum SecretaryRolePermission: string implements HasLabel
{
    case OFFICE = 'secretary|office';
    case SCHEDULE = 'secretary|schedule';
    case TIMESHEET = 'secretary|timesheet';

    public function getLabel(): string
    {
        return str($this->name)->title();
    }
}
