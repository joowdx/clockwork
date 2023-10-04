<?php

namespace App\Http\Controllers;

use App\Models\Signature;
use App\Models\User;
use Illuminate\Http\Request;

class SignatureController extends Controller
{
    public function store(User $user, Request $request)
    {
        $validated = $request->validate(['enabled' => 'required|boolean']);

        if ($user->signature === null) {
            $user->signature()->create($validated);
        }

        return redirect()->back();
    }

    public function update(Signature $signature, Request $request)
    {
        $validated = $request->validate(['enabled' => 'required|boolean']);

        $signature->update($validated);

        return redirect()->back();
    }
}
