<article style="width:100%">
    @php($byGroup = $group)
    @foreach ($employees->groupBy($byGroup ? 'groups' : 'office') as $office => $group)
        @if($byGroup && ! in_array($office, request()->groups ?? []))
            @continue
        @endif

        @for ($count = 0; $count < $copies; $count++)
            @foreach ($group->unique()->values()->chunk(60) as $page => $chunk)
                <div class="pagebreak" style="position:relative;width:fit-content;margin:auto;">
                    <img src="{{ url('img/davao-del-sur(300x300).png') }}" alt="davao-del-sur" style="position:absolute;top:40pt;left:54pt;width:90pt;">
                    <img src="{{ url('img/pgo-picto(300x300).png') }}" alt="pgo-picto" style="position:absolute;top:40pt;right:54pt;width:90pt;">
                    <table border=0 cellpadding=0 cellspacing=0 width=700 style='border-collapse:collapse;table-layout:fixed;width:fit-content;margin:auto;'>
                        <col width=70 span=10 style='width:53pt'>
                        <tr height=19 style='height:14.25pt'></tr>
                        <tr height=19 style='height:14.25pt'></tr>
                        <tr height=19 style='height:14.25pt'></tr>
                        <tr height=19 style='height:14.25pt'></tr>
                        <tr height=19 style='height:14.25pt'></tr>
                        <tr height=19 style='height:14.25pt'>
                            <td height=19 style='height:14.25pt'></td>
                            <td></td>
                            <td></td>
                            <td colspan=4 class="bahnschrift-light center">Republic of the Philippines</td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr height=19 style='height:14.25pt'>
                            <td height=19 style='height:14.25pt'></td>
                            <td></td>
                            <td></td>
                            <td colspan=4 class="uppercase bahnschrift bold center">PROVINCE OF DAVAO DEL SUR</td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr height=19 style='height:14.25pt'>
                            <td height=19 style='height:14.25pt'></td>
                            <td></td>
                            <td></td>
                            <td colspan=4 class="bahnschrift-light center">Matti, Digos City</td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr height=19 style='height:14.25pt'></tr>
                        <tr height=22 style='height:16.5pt'>
                            <td height=22 style='height:16.5pt'></td>
                            <td></td>
                            <td colspan=6 class="uppercase cascadia center">PROVINCIAL GOVERNOR'S OFFICE</td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr height=22 style='height:16.5pt'>
                            <td colspan=10 class="uppercase consolas center font-lg bold" style="text-decoration:underline;text-decoration-thickness:2pt;text-underline-offset:6pt;">
                                PROVINCIAL INFORMATION AND COMMUNICATIONS TECHNOLOGY OFFICE
                            </td>
                        </tr>
                        <tr height=19 style='height:14.25pt'></tr>
                        <tr height=26 style='height:19.5pt'>
                            <td></td>
                            <td colspan=8 class="uppercase bahnschrift font-xl center bold">TRANSMITTAL</td>
                            <td></td>
                        </tr>
                        <tr height=19 style='height:14.25pt'></tr>
                        <tr height=22 style='height:16.5pt'>
                            <td></td>
                            <td colspan=8 class="cascadia center">
                                Biometric Printouts for
                                @if(isset($from, $to))
                                    <span class="bold">
                                        {{ "{$from->format('d')}-{$to->format('d F Y')}" }}
                                    </span>
                                @else
                                    <span class="bold">
                                        {{
                                            $dates->sort()->map(fn ($date) => [
                                                'year' => $date->format('Y'),
                                                'month' => $date->format('M'),
                                                'day' => $date->format('d'),
                                            ])
                                            ->groupBy(fn ($date) => $date['month'].$date['year'])
                                            ->map(fn ($dates, $index) => collect($dates)->map(fn ($e) => $e['day'])->join(',').' '.$dates[0]['month'].' '.$dates[0]['year'])
                                            ->join(', ')
                                        }}
                                    </span>
                                @endif
                            </td>
                            <td></td>
                        </tr>
                        <tr height=19 style='height:14.25pt'>
                            <td></td>
                            <td colspan=8 class="uppercase cascadia center font-lg bold">
                                {{ $office }}
                            </td>
                            <td></td>
                        </tr>
                        <tr height=19 style='height:14.25pt'></tr>
                        @for (
                            $i = 0, $left = $i  + 1 + $page * 60, $right = $i  + 31 + $page * 60, $el = @$chunk[$left - 1], $er = @$chunk[$right - 1];
                            $i < 30;
                            $i++, $left = $i  + 1 + $page * 60, $right = $i  + 31 + $page * 60, $el = @$chunk[$left - 1], $er = @$chunk[$right - 1]
                        )
                            <tr height=20 style='height:15.0pt'>
                                <td></td>
                                <td class="consolas nowrap" colspan=4 style="{{ $left < 10 ? 'padding-left:14pt;' : ($left < 100 ? 'padding-left:7pt;' : '') }}">
                                    @if ($el)
                                        <b>{{ $left . '. ' }} </b> {{ @$er ? @$el?->ellipsize(24) : $el?->name_format->fullStartLastInitialMiddle }}
                                    @endif
                                </td>
                                <td class="consolas nowrap" colspan=4 style="{{ $right < 10 ? 'padding-left:14pt;' : ($right < 100 ? 'padding-left:7pt;' : '') }}">
                                    @if ($er)
                                        <b>{{ $right . '. ' }} </b> {{ @$er?->ellipsize(24) }}
                                    @endif
                                </td>
                                <td></td>
                            </tr>
                        @endfor
                        <tr height=19 style='height:14.25pt'></tr>
                        <tr height=19 style='mso-height-source:userset;height:14.25pt'>
                            <td height=19 style='height:14.25pt'></td>
                            <td colspan=8 rowspan=4 class="bahnschrift" width=560 style='width:424pt'>
                                By signing below, I hereby acknowledge that our office has received and reviewed the biometric printouts of all the employees enlisted above.
                            </td>
                            <td></td>
                        </tr>
                        <tr height=19 style='height:14.25pt'></tr>
                        <tr height=19 style='height:14.25pt'></tr>
                        <tr height=19 style='height:14.25pt'></tr>
                        <tr height=19 style='height:14.25pt'></tr>
                        <tr height=19 style='height:14.25pt'></tr>
                        <tr height=19 style='height:14.25pt'></tr>
                        <tr height=20 style='height:15.0pt'>
                            <td height=20 style='height:15.0pt'></td>
                            <td colspan=3></td>
                            <td></td>
                            <td></td>
                            <td colspan=3 class="relative consolas bold bottom center">
                                <livewire:print.signature />
                                {{ auth()?->user()?->name }}
                            </td>
                            <td></td>
                        </tr>
                        <tr height=19 style='height:14.25pt'>
                            <td height=19 style='height:14.25pt'></td>
                            <td colspan=3 class="arial font-xs top center overline">PERSONNEL-IN-CHARGE</td>
                            <td></td>
                            <td></td>
                            <td colspan=3 class="arial font-xs top center overline">
                                {{ auth()?->user()?->title }}
                            </td>
                            <td></td>
                        </tr>
                    </table>
                    <span class="font-xs consolas show-on-print" style="position:absolute;right:-25pt;bottom:-70pt;opacity:60%;">
                        This is a system generated report. ({{ ($page + 1) . '/' . ceil($group->count() / 60) }})
                    </span>
                </div>
            @endforeach
        @endfor
    @endforeach
</article>
