<?php

namespace App\Actions;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class RemoteFetchTimelogs
{
    public function fetch(
        string $host,
        ?string $port,
        ?string $pass,
        string $month,
        ?string $user = null,
    ) {
        Http::throw()
            ->acceptJson()
            ->withToken(config('app.remote.token'))
            ->withoutVerifying()
            ->post(config('app.remote.host').'/api/fetch/send', [
                'callback' => config('app.url').'/api/fetch/receive',
                'host' => $host,
                'port' => $port,
                'pass' => $pass,
                'month' => $month,
                'user' => encrypt($user ?? Auth::id()),
                'token' => $this->token(),
            ]);
    }

    protected function token()
    {
        $encrypter = new Encrypter(base64_decode(config('app.remote.key')), config('app.cipher'));

        return $encrypter->encrypt(config('app.remote.user'));
    }
}
