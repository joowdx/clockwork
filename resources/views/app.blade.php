<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark" data-theme="dark">
    <head>
        <meta charset="utf-8">

        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ $title ?? config('app.name', 'Laravel') }}</title>

        @yield('head')
    </head>

    <body class="font-sans antialiased">

        @yield('body')

    </body>
</html>
