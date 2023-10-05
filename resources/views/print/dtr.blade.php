<article style="width:100%;">
    @foreach($months as $month)

        @php($start = $month->isSameMonth($from) ? max($from->clone()->setMonth($month->month), $month->clone()->startOfMonth()) : $month->clone()->startOfMonth())

        @php($end = $month->isSameMonth($to) ? min($to->setMonth($month->month), $month->clone()->endOfMonth()) : $month->clone()->endOfMonth())

        <div class="pagebreak" style="display:flex;align-items:center;justify-content:center;max-width:620pt;margin:auto;">
            @for ($side = 0; $side < 2; $side++)
                <div style="width:100%;border-width:1pt;border-style:@if($side==0)none dashed none none; @else none none none dashed @endif">
                    <table border=0 cellpadding=0 cellspacing=0 style="border-collapse:collapse;table-layout:fixed;width:fit-content;margin:auto!important;">
                        <col width=57 span=6>
                        <tr>
                            <td colspan=6 class="relative">
                                <img src="{{ url('img/davao-del-sur(300x300).png') }}" alt="davao-del-sur" class="absolute" style="width:36pt;opacity:0.1;top:15pt;right:0;">
                            </td>
                        </tr>
                        <tr>
                            <td class="italic font-xs arial" colspan=6 >Civil Service Form No. 48</td>
                        </tr>
                        <tr>
                            <td class="center bahnschrift font-xl bold" colspan=6>DAILY TIME RECORD</td>
                        </tr>
                        <tr>
                            <td class="center font-xs bold" colspan=6>
                                <span style='font-variant-ligatures: normal;font-variant-caps: normal;orphans: 2;widows: 2;-webkit-text-stroke-width: 0px;text-decoration-thickness: initial;text-decoration-style: initial;text-decoration-color: initial'>
                                    -----o0o-----
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan=6></td>
                        </tr>
                        <tr>
                            <td class="underline uppercase bold consolas font-lg center" colspan=6>
                                {{ $employee->name_format->fullStartLastInitialMiddle }}
                            </td>
                        </tr>
                        <tr>
                            <td class="consolas top center font-xs" colspan=6>
                                Employee
                            </td>
                        </tr>
                        <tr>
                            <td class="arial font-xs bottom right" colspan=2 style="padding-bottom:2.5pt;padding-right:10pt;">For the month of:</td>
                            <td class="underline font-md consolas center bold" colspan=4>{{ "{$start->format('d')}-{$end->format('d F Y')}" }}</td>
                        </tr>
                        <tr>
                            <td class="font-xs left middle arial" colspan=2 rowspan=2 height=40>Official hours for <br> arrival &amp; departure </td>
                            <td class="relative arial font-xs bottom left nowrap" colspan=1>
                                <span class="absolute" style="bottom:1pt;left:-11pt;">
                                    Regular Days
                                </span>
                            </td>
                            <td class="underline consolas font-sm bold bottom left" colspan=3>{{ $time->weekdays }}</td>
                        </tr>
                        <tr>
                            <td class="relative arial font-xs bottom left" colspan=1>
                                <span class="absolute" style="bottom:1pt;left:-11pt;">
                                    Saturdays
                                </span>
                            </td>
                            <td class="underline consolas font-sm bold bottom left nowrap" colspan=3> {{ $time->weekends }} </td>
                        </tr>
                        <tr>
                            <td colspan=6></td>
                        </tr>
                        <tr class="font-sm bold">
                            <td class="border center middle cascadia" rowspan=2 height=42 width=58>DAY</td>
                            <td class="border center middle cascadia" colspan=2 width=116>AM</td>
                            <td class="border center middle cascadia" colspan=2 width=116>PM</td>
                            <td class="border center middle cascadia" rowspan=2 width=58>Under<br>time</td>
                        </tr>
                        <tr class="font-sm bold">
                            <td class="border cascadia center" width=58 style="font-size:7.5pt;">Arrival</td>
                            <td class="border cascadia center" width=58 style="font-size:7.5pt;">Departure</td>
                            <td class="border cascadia center" width=58 style="font-size:7.5pt;">Arrival</td>
                            <td class="border cascadia center" width=58 style="font-size:7.5pt;">Departure</td>
                        </tr>
                        @php($utTotal = 0)

                        @php($daysTotal = 0)

                        @php($forChange = false)

                        @for ($day = 1; $day <= 31; $day++)
                            @php($date = $month->clone()->setDay($day))

                            @php(@[$in1, $out1, $in2, $out2, $ut] = array_values(@$attlogs[$date->format('Y-m-d')] ?? []))

                            @if ($date->between($start, $end))
                                <tr @class(['weekend' => $weekend = $date->format('d') == $day && $date->isWeekend(), 'invalid' => @$ut->invalid && (! $employee->regular || $date->isWeekday()) && $date->format('d') == $day && $employee->logsForTheDay($date)->isNotEmpty()])>
                                    <td class="border right courier bold" style="padding-right:14pt;">
                                        {{ $date->format('d') == $day ? $day : '' }}
                                    </td>
                                    @if ($weekend && $employee->logsForTheDay($date)->isEmpty())
                                        <td colspan=4 class="border center consolas">
                                            {{ $date->format('l') }}
                                        </td>
                                    @else
                                        <td class="relative border consolas" width=58>
                                            <div class="font-sm nowrap consolas {{ @$in1?->scanner->name }}" style="margin-left:5pt;">
                                                {{ @$in1?->time->format('H:i') }}
                                            </div>
                                            @if (@$ut->in1)
                                                <span class="undertime-badge">
                                                    {{ @$ut->in1 }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="relative border consolas" width=58>
                                            <div class="font-sm nowrap consolas {{ @$out1?->scanner->name }}" style="margin-left:5pt;">
                                                {{ @$out1?->time->format('H:i') }}
                                            </div>
                                            @if (@$ut->out1)
                                                <span class="undertime-badge">
                                                    {{ @$ut->out1 }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="relative border consolas" width=58>
                                            <div class="font-sm nowrap consolas {{ @$in2?->scanner->name }}" style="margin-left:5pt;">
                                                {{ @$in2?->time->format('H:i') }}
                                            </div>
                                            @if (@$ut->in2)
                                                <span class="undertime-badge">
                                                    {{ @$ut->in2 }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="relative border consolas" width=58>
                                            <div class="font-sm nowrap consolas {{ @$out2?->scanner->name }}" style="margin-left:5pt;">
                                                {{ @$out2?->time->format('H:i') }}
                                            </div>
                                            @if (@$ut->out2)
                                                <span class="undertime-badge">
                                                    {{ @$ut->out2 }}
                                                </span>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="relative border consolas" width=58>
                                        <div class="font-sm nowrap consolas right" style="margin-left:auto;width:fit-content;padding-right:5pt;">
                                            {{ @$ut?->total ?: '' }}
                                        </div>
                                    </td>
                                </tr>
                                @if (@$calculate)
                                    @php($utTotal += (@$ut?->total ?? 0))

                                    @php($daysTotal += @$ut?->count ? 1 : 0)

                                    @php($forChange = @$forChange || @$ut->invalid && (! $employee->regular || $date->isWeekday()) && $date->format('d') == $day && $employee->logsForTheDay($date)->isNotEmpty() )
                                @endif

                            @elseif($date->format('d') == $day)
                                <tr>
                                    <td class="border right courier bold" style="padding-right:14pt;">
                                        {{ $date->format('d') == $day ? $day : '' }}
                                    </td>
                                    <td class="border" colspan=5> </td>
                                </tr>
                            @else
                                <tr>
                                    <td class="border"> </td>
                                    <td class="border" colspan=6> </td>
                                </tr>
                            @endif
                        @endfor
                        <tr style="height:10pt"> </tr>
                        <tr>
                            <td colspan=2 class="font-md cascadia right bold" style="padding-right:10pt;padding-bottom:2pt;">TOTAL:</td>
                            @if (@$calculate)
                                <td colspan=2 class="underline ">
                                    <div @class(['invalid' => @$forChange, 'consolas font-md left bold']) style="width:fit-content;padding:1pt;border-radius:2.5pt;">
                                        {{ ($daysTotal ? "{$daysTotal} days " : '') }}
                                    </div>
                                </td>
                                <td colspan=2 class="underline consolas font-sm right"> {{ ($utTotal ? "t\u = {$utTotal} mins" : '') }} </td>
                            @else
                                <td colspan=4 class="underline consolas font-md left bold"> </td>
                            @endif
                        </tr>
                        <tr>
                            <td class="italic font-xs arial" colspan=6 rowspan=3>
                                I certify on my honor that the above is a true and correct report of the hours of work performed,
                                record of which was made daily at the time of arrival and departure from office.
                            </td>
                        </tr>
                        <tr>
                            <td colspan=6></td>
                        </tr>
                        <tr>
                            <td colspan=6></td>
                        </tr>
                        <tr>
                            <td class="underline" colspan=6></td>
                        </tr>
                        <tr>
                            <td class="bahnschrift-light top center font-sm" colspan=6>Employee's Signature</td>
                        </tr>
                        <tr>
                            <td class="italic arial font-xs" colspan=6>Verified as to the prescribed office hours:</td>
                        </tr>
                        <tr>
                            <td colspan=6></td>
                        </tr>
                        <tr>
                            <td class="underline" colspan=6></td>
                        </tr>
                        <tr>
                            <td class="bahnschrift-light top center font-sm" colspan=6>Supervisor</td>
                        </tr>
                        <tr>
                            <td colspan=6></td>
                        </tr>
                        <tr>
                            <td class="underline" colspan=6></td>
                        </tr>
                        <tr>
                            <td class="bahnschrift-light top center font-sm" colspan=6>Department Head</td>
                        </tr>
                        <tr>
                            <td colspan=6></td>
                        </tr>
                        <tr style="width:100%;border-width:0;border-top-width:0.5pt;border-style:dashed;border-color:#0007!important;">
                            <td colspan=1 class="relative">
                                <div class="absolute font-xxs consolas" style="opacity:0.3;transform:rotate(270deg);left:-17pt;top:10pt;">
                                    {{ $week }}
                                </div>
                                <div class="absolute consolas" style="opacity:0.3;font-size:5pt;top:0pt;">
                                    {{ collect($days)->sort()->map(fn($e) => str_pad($e, 2, '0', STR_PAD_LEFT))->join(', ') }}
                                </div>
                            </td>
                            <td colspan=4 class="relative">
                                <img src="{{ url('img/pgo-picto(300x300).png') }}" alt="pgo-picto" class="absolute" style="width:36pt;opacity:0.15;margin:auto;left:0;right:0;">
                            </td>
                            <td colspan=1></td>
                        </tr>
                        <tr>
                            <td colspan=1></td>
                            <td class="relative underline font-xs center bottom bold consolas" colspan=4 style="color:#000A;border-color:#000A!important;">
                                <livewire:print.signature />
                                {{ $user->name }}
                            </td>
                            <td colspan=1></td>
                        </tr>
                        <tr>
                            <td colspan=1> </td>
                            <td class="font-xxs center arial top" colspan=4 style="color:#000A;">
                                {{ $user->title }}
                            </td>
                            <td class="relative" colspan=1>
                                <div class="absolute consolas" style="opacity:0.3;bottom:0;right:0;font-size:4.0pt;">
                                    {{ now()->format('Y-m-d|H:i') }}
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            @endfor
        </div>
    @endforeach
</article>
