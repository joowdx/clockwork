<?php

if (! function_exists('settings')) {
    function settings(?string $key, bool $defaults = true): mixed
    {
        try {
            if (is_null($key)) {
                return null;
            }

            $value = \App\Models\Setting::get($key);

            return $defaults ? ($value ?? \App\Models\Setting::default($key)) : $value;
        } catch (\Illuminate\Database\QueryException) {
            return null;
        }
    }
}
