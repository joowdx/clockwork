<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Http\Controllers\Inertia\UserProfileController;
use Laravel\Jetstream\Jetstream;

class ProfileController extends UserProfileController
{
    public function show(Request $request)
    {
        $this->validateTwoFactorAuthenticationState($request);

        return Jetstream::inertia()->render($request, 'Profile/Show', [
            'user' => $request->user()->load('signature'),
            'signature' => $request->user()->signature?->load('specimens'),
            'confirmsTwoFactorAuthentication' => Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
            'sessions' => $this->sessions($request)->all(),
        ]);
    }
}
