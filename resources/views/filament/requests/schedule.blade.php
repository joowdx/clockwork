<section class="relative space-y-3 overflow-x-auto">
    <div>
        <h2 class="text-xl font-bold tracking-tight">
            Proposed Schedule
        </h2>

        <p class="text-gray-600">
            {{ $schedule->requestor?->name ? 'Requested by ' . $schedule->requestor->name : ''}}
        </p>
    </div>

    <div class="space-y-1">
        <div>
            <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                {{ $schedule->application?->title }}
            </h5>

            <p class="font-normal text-gray-700 dark:text-gray-400">
                {{
                    str($schedule->application?->body)
                        ->replace('<ul>', '<ul class="list-disc list-inside">')
                        ->replace('<ol>', '<ol class="list-decimal list-inside">')
                        ->toHtmlString()
                }}
            </p>
        </div>

        <div class="font-bold">
            List of employees and their respective schedules
        </div>

        <table class="w-full text-base text-left">
            <tbody>
                @foreach ($schedule->employees as $employee)
                    <tr>
                        <td scope="row" class="py-1 whitespace-nowrap">
                            {{ $employee->name }}
                        </td>
                        <td class="py-1 text-xs text-right">
                            @if ($schedule->arrangement === 'standard-work-hour')
                                {{ "{$schedule->timetable['p1']}-{$schedule->timetable['p2']} {$schedule->timetable['p3']}-{$schedule->timetable['p4']}" }}
                            @elseif($schedule->arrangement === 'work-shifting')
                                {{ "{$employee->pivot->timetable['p1']['time']}-{$employee->pivot->timetable['p2']['time']}" }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
