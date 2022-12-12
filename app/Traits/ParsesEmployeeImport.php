<?php

namespace App\Traits;

trait ParsesEmployeeImport
{
    private static array $requiredHeaders = [
        'last name',
        'first name',
    ];

    private static array $optionalHeaders = [
        'name extension',
        'middle name',
        'office',
        'regular',
        'active',
        'groups',
    ];

    private function headers(array|string $first): array
    {
        return array_flip(explode(',', strtolower(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', is_array($first) ? implode(',', $first) : $first))));
    }

    private function scanners(array $headers): array
    {
        return array_flip(array_diff(array_flip($headers), array_merge(self::$requiredHeaders, self::$optionalHeaders)));
    }

    private function uids(array $entry, array $scanners): array
    {
        return collect($scanners)->mapWithKeys(fn ($e, $f) => [$f => $entry[$scanners[$f]]])->filter()->toArray();
    }
}
