<?php

namespace App\Services;

use App\Models\Configuration;
use RuntimeException;

class ConfigurationService
{
    public function getAlert(string $type): object|array
    {
        if (! in_array($type, ['guest', 'user'])) {
            throw new RuntimeException("Invalid type value of '$type' passed.");
        }

        return Configuration::firstWhere('key', "alert_$type")?->value ?? [
            'type' => null,
            'title' => '',
            'message' => '',
        ];
    }

    public function getAlerts(): array
    {
        return [
            'user' => Configuration::firstWhere('key', 'alert_user')?->value,
            'guest' => Configuration::firstWhere('key', 'alert_guest')?->value,
        ];
    }

    public function setAlerts(array $guest = [], array $user = []): void
    {
        Configuration::upsert([
            ['key' => 'alert_user', 'value' => json_encode($user)],
            ['key' => 'alert_guest', 'value' => json_encode($guest)],
        ], ['key'], ['value']);
    }
}
