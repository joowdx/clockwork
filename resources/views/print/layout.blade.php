<html lang="en">
    <head>
        <title> @yield('title', 'Attendance') </title>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8;">

        <style>
            {!! File::get(base_path('resources/css/print.css')) !!}
            {!! File::get(base_path('resources/css/fonts.css')) !!}
        </style>

        @stack('head')
    </head>
    <body>
        <main align="center">
            @yield('content')
        </main>
    </body>
    @stack('body')
</html>
