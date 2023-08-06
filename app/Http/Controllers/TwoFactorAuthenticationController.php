<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Contracts\TwoFactorDisabledResponse;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController as ControllersTwoFactorAuthenticationController;

class TwoFactorAuthenticationController extends ControllersTwoFactorAuthenticationController
{
    public function destroy(Request $request, DisableTwoFactorAuthentication $disable)
    {
        $user = User::find($request->route('user'));

        $disable($user);

        return redirect()->back();
    }
}
