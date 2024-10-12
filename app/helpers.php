<?php

if (! function_exists('settings')) {
    function settings(?string $key = null, bool $defaults = true): mixed
    {
        if (is_null($key)) {
            return null;
        }

        try {
            $value = \App\Models\Setting::get($key);

            return $defaults ? ($value ?? \App\Models\Setting::default($key)) : $value;
        } catch (\Illuminate\Database\QueryException) {
            return null;
        }
    }
}

if (! function_exists('user')) {
    function user(): ?\App\Models\User
    {
        /** @var ?\App\Models\User */
        $user = \Illuminate\Support\Facades\Auth::user();

        return $user;
    }
}
