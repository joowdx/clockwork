@extends('print.layout')

@section('content')
    <div class="flex break-inside-avoid left">
        <div class="uppercase bahnschrift bold" style="margin-right: 10px;">
            Action
        </div>

        <label class="space-x-3 cursor-pointer" style="margin-right: 15px;">
            <x-filament::input.radio value='hide' wire:model='action' />

            <span class="font-medium text-gray-950 dark:text-white">
                Hide
            </span>
        </label>

        <label class="space-x-3 cursor-pointer">
            <x-filament::input.radio value='delete' wire:model='action' />

            <span class="font-medium text-gray-950 dark:text-white">
                Delete
            </span>
        </label>
    </div>

    <div style="display:flex;align-items:center;justify-content:center;max-width:620pt;margin:auto;">
        <table
            border="0"
            cellpadding="0"
            cellspacing="0"
            @style([
                'border-collapse:collapse',
                'table-layout:fixed',
                'width:fit-content',
                'overflow:hidden',
            ])
        >
            <tbody>
                <tr>
                    <td class="font-md bold bottom bahnschrift left">NAME</td>
                    <td colspan="4" class="uppercase font-md bottom consolas left whitespace-nowrap">
                        {{ $employee->full_name }}
                    </td>
                    <td class="font-md top courier right" colspan="5" rowspan="3">
                        <span class="uppercase bold">Mode</span>

                        <div style="display:flex;flex-wrap:wrap;">
                            <div @class(['lowercase', 'font-sm']) style="width:50%;">
                                &nbsp;
                            </div>
                            @foreach (collect(\App\Enums\TimelogMode::cases())->unique->getCode() as $mode)
                                <div @class(['lowercase whitespace-nowrap', 'font-sm']) style="width:50%;">
                                    {{ $mode->getLabel() }} = <sub>{{ $mode->getCode() }}</sub>
                                </div>
                            @endforeach
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="font-md bold bottom bahnschrift left">MONTH</td>
                    <td colspan="4" class="uppercase font-md bottom consolas left">
                        {{ $month->format('M Y') }}
                    </td>
                </tr>
                <tr>
                    <td class="font-md bold bottom bahnschrift left">DATES</td>
                    <td colspan="4" class="uppercase font-md bottom consolas left">
                        {{ str_pad($from, 2, 0, STR_PAD_LEFT) . " - " . str_pad($to, 2, 0, STR_PAD_LEFT) }}
                    </td>
                </tr>
                <tr height="22" style="height:16.5pt">
                    <td colspan="1" height="22" @class(['bold bottom nowrap font-md font-mono left' ]) style="height:16.5pt">
                        DAY <x-filament::loading-indicator wire:loading wire:target='thaumaturge' class="w-5 h-5" />
                    </td>
                    <td colspan="5"></td>
                    <td class="font-md top courier right" colspan="4">
                        <span class="uppercase bold">State</span>

                        <div style="display:flex;flex-wrap:wrap;">
                            <div @class(['lowercase font-sm']) style="width:calc(100%/3);">
                                unknown = <sup>u</sup>
                            </div>
                            <div @class(['lowercase font-sm']) style="width:calc(100%/3);">
                                in = <sup> i </sup>
                            </div>
                            <div @class(['lowercase font-sm']) style="width:calc(100%/3);">
                                out = <sup> o </sup>
                            </div>
                        </div>
                    </td>
                </tr>
                @foreach ($month->range($month->format('Y-m-') . $month->daysInMonth) as $date)
                    <tr @class(['underline' => !$loop->last, 'font-mono']) style="border-color: #8888 !important; text-decoration: none;">
                        <td @style([
                            "padding:3pt 0;",
                            "color:red;" => $timelogs
                                ->filter(fn ($t) => $t->time->isSameDay($date))
                                ->sortBy('time')
                                ->take(9)
                                ->some(fn ($t) => $t->pseudo),
                        ])>
                            <span class="bold">
                                {{ $date->format('d') }}
                            </span>
                            {{ $date->format('D') }}
                        </td>
                        @foreach ($timelogs->filter(fn ($t) => $t->time->isSameDay($date))->sortBy('time')->take(9) as $timelog)
                            <td class="relative text-sm" style="padding:1pt 0;">
                                <span class="absolute" style="bottom:5pt;left:8pt;">
                                    <sup>{{ $timelog->shadow ? 'x' : '' }}</sup>
                                </span>
                                <span class="absolute" style="bottom:4pt;left:8pt;">
                                    <sub>{{ $timelog->pseudo ? '?' : '' }}</sub>
                                </span>
                                <span
                                    class="cursor-pointer font-sm nowrap bold"
                                    wire:click="thaumaturge('{{$timelog->id}}')"
                                    wire:confirm="Are you sure you want to continue?"
                                    @style([
                                        "text-color:{$timelog->scanner->foregroundColor}!important;",
                                        "background-color:{$timelog->scanner->backgroundColor}!important;",
                                        "border-radius:2pt",
                                    ])
                                >
                                    {{ $timelog->time->format('H:i') }}
                                </span>

                                <span class="absolute">
                                    <sup>{{ match(true) { $timelog->in => 'i', $timelog->out => 'o', default => 'u' } }}</sup><sub>{{ $timelog->mode->getCode() }}</sub>
                                </span>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                <tr></tr>
                <tr>
                    <td colspan="10" rowspan="3" @class(['top courier', 'left'])>
                        <span @class(['uppercase bold font-md'])>Scanners</span>
                        <div @style(["display:flex;flex-wrap:wrap;overflow:hidden;"])>
                            @foreach ($employee->scanners->sortBy('name') as $scanner)
                                <div class="lowercase font-xs" style="padding:1pt;">
                                    <div
                                        @style([
                                            'padding:2pt 3pt 0',
                                            'border-radius:2pt',
                                            'text-color:' . $scanner->foregroundColor . '!important;',
                                            'background-color:' . $scanner->backgroundColor . '!important;',
                                        ])
                                    >
                                        {{ "{$scanner->name} ({$scanner->pivot->uid})" }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
@endsection
