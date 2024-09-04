<?php

namespace App\Actions;

use Illuminate\Support\Facades\Http;

class FetchHolidays
{
    public function __invoke(int $year, bool $throw = true): ?array
    {
        $response = Http::get(config('services.calendarific.url'), [
            'type' => 'national',
            'country' => 'PH',
            'api_key' => config('services.calendarific.key'),
            'year' => $year,
        ]);

        $response->throwIf($throw);

        return $response->json('response.holidays');
    }
}
