<?php

namespace App\Http\Controllers\Api;

use App\Actions\UpsertTimelogs;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fetch\ReceiveRequest;
use App\Http\Requests\Fetch\SendRequest;
use App\Jobs\FetchTimelogs;

class FetchController extends Controller
{
    public function send(SendRequest $request)
    {
        FetchTimelogs::dispatch(...$request->data());
    }

    public function receive(ReceiveRequest $request)
    {
        if ($request->success()) {
            app(UpsertTimelogs::class, [
                'user' => $request->user(),
                'scanner' => $request->scanner(),
                'timelogs' => $request->timelogs(),
                'month' => $request->month(),
            ])->execute();
        }

        $request->notify();
    }
}
