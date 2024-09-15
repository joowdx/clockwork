<section class="relative space-y-3 overflow-x-auto">
    <div>
        <h2 class="text-xl font-bold tracking-tight">
            {{ $schedule->global ? 'Global' : 'Proposed' }} Schedule
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

        @if ($schedule->global)
            <div>
                <div>
                    <h3 class="text-base font-semibold leading-7 text-gray-900 dark:text-gray-100">Schedule Information</h3>
                    <p class="max-w-2xl mt-1 text-sm leading-6 text-gray-500">Schedule period and other miscellaneous information.</p>
                </div>
                <div class="mt-3 border-t border-gray-100 dark:border-gray-700">
                    <dl class="divide-y divide-gray-100 dark:border-gray-700">
                        <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">
                                Period
                            </dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 dark:text-gray-300 sm:col-span-2 sm:mt-0">
                                {{ $schedule->start->format('j F Y') . " – ".  $schedule->end->format('j F Y')}}
                            </dd>
                        </div>
                    </dl>
                    <dl class="divide-y divide-gray-100 dark:border-gray-700">
                        <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">
                                Time
                            </dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 dark:text-gray-300 sm:col-span-2 sm:mt-0">
                                {{ $schedule->time }}
                            </dd>
                        </div>
                    </dl>
                    <dl class="divide-y divide-gray-100 dark:border-gray-700">
                        <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">
                                Days
                            </dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-700 dark:text-gray-300 sm:col-span-2 sm:mt-0">
                                {{ ucfirst($schedule->days) }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        @else
            <div class="font-bold">
                List of employees and their respective schedules
            </div>
        @endif

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
