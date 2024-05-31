<?php

namespace App\Enums\Permissions;

use Filament\Support\Contracts\HasLabel;

enum EmployeePermission: string implements HasLabel
{
    case VIEW_ALL = 'employee|10';
    case VIEW = 'employee|11';
    case CREATE = 'employee|20';
    case UPDATE = 'employee|30';
    case DELETE = 'employee|40';
    case BATCH_DELETE = 'employee|41';
    case RESTORE = 'employee|50';
    case BATCH_RESTORE = 'employee|51';
    case FORCE_DELETE = 'employee|60';
    case BATCH_FORCE_DELETE = 'employee|61';

    public function getLabel(): ?string
    {
        return match ($this->name) {
            default => str($this->name)->replace('_', '-')->lower(),
        };
    }
}
