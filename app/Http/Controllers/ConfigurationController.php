<?php

namespace App\Http\Controllers;

use App\Services\ConfigurationService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

class ConfigurationController extends Controller
{
    public function index(Authenticatable $user, ConfigurationService $service)
    {
        abort_unless($user->administrator, 403);

        return inertia('Configuration/Index', [
            'alerts' => $service->getAlerts(),
        ]);
    }

    public function alert(
        Authenticatable $user,
        Request $request,
        ConfigurationService $service
    ) {
        abort_unless($user->administrator, 403);

        $validated = $request->validate([
            'user.type' => 'nullable|string|in:error,info,question,success,warning',
            'user.title' => 'required_with:user.message|nullable|string',
            'user.message' => 'required_with:user.title|nullable|string',
            'user.dismissable' => 'required_with:user.title|nullable|boolean',
            'guest.type' => 'nullable|string|in:error,info,question,success,warning',
            'guest.title' => 'required_with:guest.message|nullable|string',
            'guest.message' => 'required_with:guest.title|nullable|string',
            'guest.dismissable' => 'required_with:user.title|nullable|boolean',
        ], [], [
            'user.type' => 'type',
            'user.title' => 'title',
            'user.message' => 'message',
            'user.dismissable' => 'dismissable',
            'guest.type' => 'type',
            'guest.title' => 'title',
            'guest.message' => 'message',
            'guest.dismissable' => 'dismissable',
        ]);

        $service->setAlerts(...$validated);

        return redirect()->back();
    }
}
