@extends('app')

@section('head')

    <script src="{{ mix('js/livewire.js') }}" defer></script>

    @livewireStyles

    @stack('head')

@endsection

@section('body')

    <x-jet-banner />

    <div class="min-h-screen bg-gray-800">
        @include('navigation-menu')

        <!-- Page Heading -->
        @if (isset($header))
            <header class="bg-black shadow">
                <div class="px-4 py-6 mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>

    @stack('modals')

    @stack('body')

    @livewireScripts

@endsection
