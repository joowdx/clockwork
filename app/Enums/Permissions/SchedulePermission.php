<?php

namespace App\Enums\Permissions;

use Filament\Support\Contracts\HasLabel;

enum SchedulePermission: string implements HasLabel
{
    case VIEW_ALL = 'schedule|10';
    case VIEW = 'schedule|11';
    case CREATE = 'schedule|20';
    case UPDATE = 'schedule|30';
    case DELETE = 'schedule|40';
    case BATCH_DELETE = 'schedule|41';
    case RESTORE = 'schedule|50';
    case BATCH_RESTORE = 'schedule|51';
    case FORCE_DELETE = 'schedule|60';
    case BATCH_FORCE_DELETE = 'schedule|61';

    public function getLabel(): ?string
    {
        return match ($this->name) {
            default => str($this->name)->replace('_', '-')->lower(),
        };
    }
}
