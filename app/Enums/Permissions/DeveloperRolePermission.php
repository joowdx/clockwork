<?php

namespace App\Enums\Permissions;

use Filament\Support\Contracts\HasLabel;

enum DeveloperRolePermission: string implements HasLabel
{
    case ROUTE = 'developer|route';
    case TOKEN = 'developer|token';
    case SETTING = 'developer|setting';

    public function getLabel(): string
    {
        return str($this->name)->title();
    }
}
