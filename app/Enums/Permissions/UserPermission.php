<?php

namespace App\Enums\Permissions;

use Filament\Support\Contracts\HasLabel;

enum UserPermission: string implements HasLabel
{
    case ACCESS_CONTROL = 'user|01';
    case VIEW_ALL = 'user|10';
    case VIEW = 'user|11';
    case CREATE = 'user|20';
    case UPDATE = 'user|30';
    case DELETE = 'user|40';
    case BATCH_DELETE = 'user|41';
    case RESTORE = 'user|50';
    case BATCH_RESTORE = 'user|51';
    case FORCE_DELETE = 'user|60';
    case BATCH_FORCE_DELETE = 'user|61';

    public function getLabel(): ?string
    {
        return match ($this->name) {
            default => str($this->name)->replace('_', '-')->lower(),
        };
    }
}
