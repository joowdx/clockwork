<html lang="en">
    <head>
        <title>PRINTOUT</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        @vite(['resources/css/print.css'])
        @livewireStyles()
        @stack('styles')
    </head>
    <body>
        <main align=center>
            @if (@$transmittal)
                <livewire:print.transmittal :employees="$employees" :from="$from" :to="$to" />
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
        @stack('scripts')
    </body>
    <style>
        @foreach ($employees->flatMap->scanners->unique('name') as $scanner)
            .{{$scanner->name}} {
                background-color: {{strtolower($scanner->print_background_colour) === '#ffffff' ? 'transparent' : $scanner->print_background_colour}};
                color: {{$scanner->print_text_colour}};
                width: fit-content;
            }
        @endforeach
    </style>
</html>
