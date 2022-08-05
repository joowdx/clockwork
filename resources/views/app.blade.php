<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>

        <meta charset="utf-8">

        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ $title ?? config('app.name', 'Laravel') }}</title>

        <link rel="preload" href="{{ url('/fonts/nunito/XRXV3I6Li01BKofINeaB.woff2') }}" as="font" crossorigin>

        <link rel="stylesheet" href="{{ mix('css/app.css') }}">

        @yield('head')

    </head>

    <body class="font-sans antialiased">

        @yield('body')

    </body>
</html>
