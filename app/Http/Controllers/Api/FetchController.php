<?php

namespace App\Http\Controllers\Api;

use App\Actions\UpsertTimelogs;
use App\Http\Controllers\Controller;
use App\Jobs\FetchTimelogs;
use App\Models\Scanner;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FetchController extends Controller
{
    public function send(Request $request)
    {
        @[
            'callback' => $callback,
            'host' => $host,
            'port' => $port,
            'pass' => $pass,
            'month' => $month,
            'user' => $user,
            'token' => $token
        ] = $request->validate([
            'callback' => 'required|url',
            'host' => 'required|string',
            'port' => 'nullable|integer|min:1',
            'pass' => 'nullable|string',
            'month' => 'nullable|string|date_format:Y-m',
            'user' => 'required|string|ulid',
            'token' => ['required', 'string', function ($attribute, $value, $fail) {
                try {
                    decrypt($value);
                } catch (DecryptException) {
                    $fail('Invalid token.');
                }
            }],
        ]);

        FetchTimelogs::dispatchSync($host, $month, $port, $pass, decrypt($token), $callback, user: $user);
    }

    public function receive(Request $request)
    {
        @[
            'status' => $status,
            'message' => $message,
            'user' => $user,
            'data' => $data,
        ] = $request->validate([
            'status' => 'nullable|string',
            'message' => 'nullable|string',
            'user' => 'required|string|ulid|exists:users,id',
            'data' => 'nullable|json',
        ]);

        @[
            'host' => $host,
            'timelogs' => $timelogs,
            'month' => $month,
        ] = json_decode($data, true);

        $user = User::findOrFail($user);

        if ($status === 'success') {
            $scanner = Scanner::where('host', $host)->firstOrFail();

            app(UpsertTimelogs::class, [
                'user' => $user,
                'scanner' => $scanner,
                'timelogs' => collect($timelogs)->map(fn ($timelog) => [...$timelog, 'device' => $scanner->uid]),
                'month' => Carbon::parse($month),
            ])->execute();

            Notification::make()
                ->title('Fetch successful')
                ->body(
                    str(<<<HTML
                        Timelogs of <i>{$scanner->name}</i> has been successfully fetched from the device <br>
                        <i>You may have to wait for a bit before the employees' records are updated</i>
                    HTML)
                        ->squish()
                        ->trim()
                        ->toHtmlString()
                )
                ->sendToDatabase($user, true);
        } else {
            if ($host) {
                $scanner = Scanner::where('host', $host)->firstOrFail();

                Notification::make()
                    ->title('Fetch failed')
                    ->body(str("Errors occurred <i>{$scanner->name}</i>: <br> ".$message)->toHtmlString())
                    ->sendToDatabase($user, true);
            } else {
                Notification::make()
                    ->title('Fetch failed')
                    ->body($message)
                    ->sendToDatabase($user, true);
            }
        }
    }
}
