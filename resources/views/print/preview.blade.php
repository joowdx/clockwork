@inject('period', 'Carbon\CarbonPeriod')
<article class="no-print" style="width:100%;">
    <div class="no-print" style="margin:auto;width:fit-content;">
        <table class="no-print" border="0" cellpadding="0" cellspacing="0" width="640">
            <tbody>
                <tr>
                    <td colspan="10" class="bold center bahnschrift font-xl" width="640" style="width:480pt"></td>
                </tr>
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
                    <td class="underline bottom nowrap cascadia font-xs" colspan="1">
                        <sub>i</sub> = in
                    </td>
                    <td class="underline bottom nowrap cascadia font-xs" colspan="1">
                        <sub>o</sub> = out
                    </td>
                    <td class="underline bottom nowrap cascadia font-xs" colspan="6"></td>
                </tr>
                @foreach ($days = ($period->create($from, $to)) as $date)
                    <tr height="20" @class(['weekend' => $date->isWeekend()])>
                        <td colspan="@if($absent = $employee->absentForTheDay($date) && $date->isWeekDay()) 10 @else 2 @endif" height="20" @class(['font-sm nowrap consolas',]) style="overflow:hidden;">
                            {{$date->format('D d-m-y')}}
                            @if ($absent)
                                @for ($l = 0; $l < 38; $l++)
                                    &nbsp;
                                @endfor
                            @endif
                        </td>
                        @php($i = 0)
                        @foreach ($employee->logsForTheDay($date) as $key => $log)
                            @php($i++)
                            <td style="position:relative;">
                                <div class="font-sm nowrap consolas bold {{ $log->scanner->name }}" style="padding:0;">
                                    {{ $log->time->format('H:i') }}
                                </div>
                                <small class="center" style="position:absolute;bottom:-2pt;left:28pt;width:5pt;text-align:center;">
                                    {{ $log->in ? 'i' : 'o' }}
                                </small>
                            </td>
                        @endforeach
                        @if (8 - $i > 0)
                            <td colspan="{{ 8 - $i }}"> </td>
                        @endif
                    </tr>
                @endforeach
                <tr height="20">
                    <td colspan="10"> </td>
                </tr>
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
            </tbody>
        </table>
    </div>
</article>
