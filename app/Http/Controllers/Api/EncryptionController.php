<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EncryptionController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate(['message' => 'required|string|max:255']);

        return ['encrypted' => encrypt($request->message)];
    }
}
