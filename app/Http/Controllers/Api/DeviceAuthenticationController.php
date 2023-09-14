<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeviceAuthenticationController extends Controller
{
    public function authenticate(Request $request)
    {
        return response([
            'user' => $request->user()->makeHidden(['type', 'title', 'disabled'])->toArray(),
            'token' => $request->user()->createToken($request->device_name ?? $request->ip() ?? '')->plainTextToken,
        ], 200);
    }

    public function deauthenticate(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
    }

    public function destroyAllSession(Request $request)
    {
        $request->user()->tokens()->delete();

        if (config('session.driver') !== 'database') {
            return;
        }

        DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->delete();
    }
}
