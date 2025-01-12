<div class="min-h-screen flex items-center justify-center relative">
    <div class="background absolute inset-0 bg-cover bg-center z-0" style="background-image: url('/svg/background.svg');"></div>

    <div class="absolute inset-0 bg-black bg-opacity-30"></div>

    <div class="relative z-10 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden w-full max-w-5xl grid grid-cols-1 md:grid-cols-2">
        <div class="p-8 flex flex-col justify-center">
            <section class="grid auto-cols-fr gap-y-6">
                <header class="flex flex-col items-center fi-simple-header">
                    <div class="flex justify-end w-full">
                        @if($themer ?? true)
                            <x-filament-panels::theme-switcher />
                        @endif
                    </div>

                    <h1 class="text-3xl font-bold tracking-tight text-center fi-simple-header-heading text-gray-950 dark:text-white">
                        @yield('heading', $this->getHeading())
                    </h1>
        
                    <p class="mt-2 text-sm text-center text-gray-500 fi-simple-header-subheading dark:text-gray-400">
                        @yield('subheading', $this->getSubHeading())
                    </p>
                </header>

                @yield('content')

                <footer>
                    @yield('footer')
                </footer>
            </section>
        </div>

        <div class="bg-white dark:bg-gray-900 min-h-fit flex items-center justify-center">
            <div class="relative w-full max-w-xs min-h-[28em] flex flex-col items-center justify-center text-center">
                <div class="w-52">
                    @include('logo')
                </div>

                @include('banner')
            </div>
        </div>
    </div>

    <x-filament-actions::modals />
</div>

@push('styles')
<style>
    .background {
        background-size: cover;
        background-position: center;
        width: 100%;
        height: 100%;
    }
</style>
@endpush