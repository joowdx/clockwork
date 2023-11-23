<?php

use App\Models\Assignment;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('presence', fn (Authenticatable $authenticated) => $authenticated);

Broadcast::channel('administrators', fn (Authenticatable $authenticated) => $authenticated->administrator);

Broadcast::channel('users.{user}', function (Authenticatable $authenticated, string $user) {
    return $authenticated->id === $user;
});

Broadcast::channel('scanners.{scanner}', function (Authenticatable $authenticated, string $scanner) {
    return $authenticated->administrator ?: Assignment::whereScannerId($scanner)->whereUserId($authenticated->id)->exists();
});
