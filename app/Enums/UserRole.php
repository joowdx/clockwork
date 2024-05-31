<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case ROOT = 'root';
    case SUPERUSER = 'superuser';
    case DEVELOPER = 'developer';
    case EXECUTIVE = 'executive';
    case BUREAUCRAT = 'bureaucrat';
    case DIRECTOR = 'director';
    case MANAGER = 'manager';
    case SECRETARY = 'secretary';
    case SECURITY = 'security';
    case DRIVER = 'driver';
    case NONE = '';

    public static function escalatable(bool $value = false)
    {
        $escalatable = [
            UserRole::MANAGER->value,
            UserRole::DIRECTOR->value,
            UserRole::BUREAUCRAT->value,
            UserRole::EXECUTIVE->value,
        ];

        return collect(UserRole::cases())
            ->filter(fn ($role) => in_array($role->value, $escalatable))
            ->mapWithKeys(fn ($role) => [$value ? $role->getLabel() : $role->value => $value ? $role->value : $role->getLabel()])
            ->toArray();
    }

    public static function requestable(bool $value = false)
    {
        $requestables = [
            UserRole::MANAGER->value,
            UserRole::DIRECTOR->value,
            UserRole::BUREAUCRAT->value,
            UserRole::EXECUTIVE->value,
        ];

        return collect(UserRole::cases())
            ->filter(fn ($role) => in_array($role->value, $requestables))
            ->mapWithKeys(fn ($role) => [$value ? $role->getLabel() : $role->value => $value ? $role->value : $role->getLabel()])
            ->toArray();
    }

    public function alias(): ?string
    {
        return settings($this->value);
    }

    public function getLabel(bool $aliased = true): ?string
    {
        if ($aliased) {
            return str($this->alias() ?: $this->name)->lower()->headline();
        }

        return str($this->name)->lower()->headline();
    }
}
