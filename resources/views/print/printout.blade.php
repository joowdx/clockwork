<html lang="en">
    <head>
        <title>PRINTOUT</title>
        <meta http-equiv=Content-Type content="text/html; charset=windows-1252">
        <link rel="stylesheet" href="{{ asset('css/print.css') }}">
        @livewireStyles()
        @stack('styles')
    </head>
    <body>
        <main align=center>
            @if ($transmittal)
                <livewire:print.transmittal :employees="$employees" :from="$from" :to="$to" />
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
                    @if($csc_format ?? $employee->csc_format)
                        <livewire:print.dtr :employee="$employee" :from="$from" :to="$to" />
                    @else
                        <livewire:print.attlogs :employee="$employee" :from="$from" :to="$to" />
                    @endif
                @endforeach
            @endif
        </main>
        @livewireScripts()
        @stack('scripts')
    </body>
    <style>
        @foreach ($employees->flatMap->scanners->unique('name') as $scanner)
            .{{$scanner->name}} {
                background-color: {{$scanner->printBackgroundColour}};
                color: {{$scanner->printTextColour}};
                width: fit-content;
            }
        @endforeach
    </style>
</html>
