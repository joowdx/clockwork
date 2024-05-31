@props([
    'heading' => null,
    'subheading' => 'Clockwork',
])

<div {{ $attributes->class(['fi-simple-page']) }}>
    <section class="grid auto-cols-fr gap-y-6">
        <header class="flex flex-col items-center fi-simple-header">
            <div class="w-1/4 pb-3">
                @include('logo')
            </div>

            <h1 class="text-2xl font-bold tracking-tight text-center fi-simple-header-heading text-gray-950 dark:text-white">
                {{ $this->getHeading() }}
            </h1>

            <p class="mt-2 text-sm text-center text-gray-500 fi-simple-header-subheading dark:text-gray-400">
                {{ $this->getSubHeading() }}
            </p>
        </header>

        @yield('content')
    </section>

    @if (! $this instanceof \Filament\Tables\Contracts\HasTable)
        <x-filament-actions::modals />
    @endif
</div>
