@inject('timelog', 'App\Services\TimeLogService')
@inject('period', 'Carbon\CarbonPeriod')

<article style="width:100%">
    @for ($x = 0; $x < $pages = ceil($period->create($from, $to)->count() / 31); $x++)
        <div class="pagebreak" style="margin-top:20pt;">
            <table border="0" cellpadding="0" cellspacing="0" width="640">
                <tbody>
                    <tr height="20">
                        <td colspan="10" rowspan="2" height="40" class="bold center bahnschrift font-xl" width="640" style="height:30.0pt;width:480pt">DAILY TIME RECORD</td>
                    </tr>
                    <tr height="20"></tr>
                    <tr height="20">
                        <td height="20" class="font-md bold bottom bahnschrift">NAME</td>
                        <td colspan="9" class="uppercase font-md bottom consolas">
                            {{ $employee->nameFormat->fullStartLast }}
                        </td>
                    </tr>
                    <tr height="20">
                        <td height="20" class="font-md bold bottom bahnschrift">FROM</td>
                        <td colspan="9" class="uppercase font-md bottom consolas">
                            {{ $from->format('D d-M-Y') }}
                        </td>
                    </tr>
                    <tr height="20">
                        <td height="20" class="font-md bold bottom bahnschrift">TO</td>
                        <td colspan="9" class="uppercase font-md bottom consolas">
                            {{ $to->format('D d-M-Y') }}
                        </td>
                    </tr>
                    <tr height="22" style="height:16.5pt">
                        <td colspan="2" height="22" class="underline bold bottom nowrap cascadia font-md" style="height:16.5pt">DATE</td>
                        <td class="underline bold bottom nowrap cascadia font-md">IN</td>
                        <td class="underline bold bottom nowrap cascadia font-md">OUT</td>
                        <td class="underline bold bottom nowrap cascadia font-md">IN</td>
                        <td class="underline bold bottom nowrap cascadia font-md">OUT</td>
                        <td class="underline bold bottom nowrap cascadia font-md">IN</td>
                        <td class="underline bold bottom nowrap cascadia font-md">OUT</td>
                        <td class="underline bold bottom nowrap cascadia font-md">IN</td>
                        <td class="underline bold bottom nowrap cascadia font-md">OUT</td>
                    </tr>
                    @foreach ($days = ($period->create($from->clone()->addDays($x * 31), $x < $pages && ($end = $from->clone()->addDays(($x + 1) * 31))->lt($to) ? $end->subDay() : $to)) as $date)
                        <tr height="20" @class(['weekend' => $date->isWeekend(), 'absent' => $employee->absentForTheDay($date) && $date->isWeekDay()])>
                            <td colspan="2" height="20" @class(['font-sm nowrap consolas',])>
                                {{  $date->format('D d-m-y') }}
                            </td>
                            @php($i = 0)
                            @foreach ($employee->logsForTheDay($date) as $key => $log)
                                @if ($log->in && $i % 2 == 0 || $log->out && $i % 2 == 1)
                                    @php($i++)
                                @else
                                    <td></td>
                                    @php($i+=2)
                                @endif
                                <td>
                                    <div class="font-sm nowrap consolas bold {{ $log->scanner->name }}"> {{ $log->time->format('H:i') }} </div>
                                </td>
                            @endforeach
                            @if (8 - $i > 0)
                                <td colspan="{{ 8 - $i }}"> </td>
                            @endif
                        </tr>
                    @endforeach
                    @for ($i = 31 - $days->count() + 1; $i > 0; $i--)
                        <tr height="20"> </tr>
                    @endfor
                    <tr height="20">
                        <td class="bold bottom nowrap cascadia font-md" style="border-bottom: none">
                            SCANNERS
                        </td>
                    </tr>
                    @foreach ($employee->scanners->chunk(5) as $chunked)
                        <tr height="20">
                            @foreach ($chunked as $scanner)
                                <td colspan="2" class="uppercase font-xs nowrap consolas scanner bold {{ $scanner->name }}">
                                    {{ $scanner->name }} ({{ str_pad($scanner->pivot->uid, 5, 0, STR_PAD_LEFT) }})
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    <tr height="20"></tr>
                    <tr height="20"></tr>
                    <tr height="20"></tr>
                    <tr height="20"></tr>
                    <tr height="20"></tr>
                    <tr height="20">
                        <td colspan="6"></td>
                        <td colspan="4" class="underline uppercase bold center bottom nowrap bahnschrift">
                            {{ auth()->user()?->name }}
                        </td>
                    </tr>
                    <tr height="20">
                        <td colspan="3" height="20" class="font-sm nowrap consolas bold">
                            DATE: <span class="uppercase">{{ now()->format('d M Y H:i') }}</span>
                        </td>
                        <td colspan="3"></td>
                        <td colspan="4" class="center nowrap bahnschrift-light">
                            {{ auth()->user()?->title }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endfor
</article>
