<html lang="en">
    <head>
        <title>PRINTOUT</title>
        <meta http-equiv=Content-Type content="text/html; charset=windows-1252">
        @vite(['resources/css/print.css'])
        @livewireStyles()
        @stack('styles')
    </head>
    <body>
        <main align=center>
            @if (@$transmittal)
                @for ($i = 0; $i < 2; $i++)
                    <livewire:print.transmittal :employees="$employees" :from="$from" :to="$to" />
                @endfor
                @foreach ($employees->groupBy('office') as $office => $employeeGroup)
                    @foreach ($employeeGroup as $employee)
                        @if($csc_format ?? $employee->csc_format)
                            <livewire:print.dtr :employee="$employee" :from="$from" :to="$to" />
                        @else
                            <livewire:print.attlogs :employee="$employee" :from="$from" :to="$to" />
                        @endif
                    @endforeach
                @endforeach
            @else
                @foreach ($employees as $employee)
                    @if(request()->by == 'search')
                        <livewire:print.preview :employee="$employee" :from="$from" :to="$to" />
                    @elseif($csc_format ?? @$employee->csc_format)
                        <livewire:print.dtr :employee="$employee" :from="$from" :to="$to" />
                    @else
                        <livewire:print.attlogs :employee="$employee" :from="$from" :to="$to" />
                    @endif
                @endforeach
            @endif
        </main>
        {{-- @livewireScripts() --}}
        @stack('scripts')
    </body>
    <style>
        @foreach ($employees->flatMap->scanners->unique('name') as $scanner)
            .{{$scanner->name}} {
                background-color: {{$scanner->print_background_colour}};
                color: {{$scanner->print_text_colour}};
                width: fit-content;
            }
        @endforeach
    </style>
</html>
