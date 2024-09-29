<x-filament-panels::page class="fi-dashboard-page">
    @if (is_null($export))
        @push('styles')
            @vite(['resources/css/blade.css'])
        @endpush
    @else
        @push('styles')
            <style>
                {!! File::get(base_path('resources/css/print.css')) !!}
                {!! File::get(base_path('resources/css/fonts.css')) !!}

                <style>
                    .undertime-badge {
                        width: 12pt !important;
                        height: 12pt !important;
                    }
                    @media (prefers-color-scheme: dark) {
                        .undertime-badge {
                            color: gray;
                            background-color: #FFF;
                        }
                        td {
                            border-color: #333 !important;
                        }
                    }
                </style>
            </style>
        @endpush
    @endif

    @if (method_exists($this, 'filtersForm'))
        {{ $this->filtersForm }}
    @endif

    <x-filament-widgets::widgets
        :columns="$this->getColumns()"
        :data="[...(property_exists($this, 'filters') ? ['filters' => $this->filters] : []), ...$this->getWidgetData()]"
        :widgets="$this->getVisibleWidgets()"
    />

    @if ($export)
        <section>
            {{ $this->infolist }}
        </section>
    @else
        <section>
            <div class="max-w-screen-xl px-4 py-8 mx-auto lg:py-16 lg:px-6">
                <div class="py-8">
                    @include('banner')
                </div>
                <div class="max-w-screen-sm mx-auto text-center">
                    <h1 class="mb-4 font-extrabold tracking-tight text-7xl lg:text-9xl text-primary-600 dark:text-primary-500">404</h1>
                    <p class="mb-4 text-3xl font-bold tracking-tight text-gray-900 md:text-4xl dark:text-white">Something's missing.</p>
                    <p class="mb-4 text-lg font-light text-gray-500 dark:text-gray-400">Sorry, we can't find the resource you are looking for. </p>
                    <a href="{{ url('/') }}" class="inline-flex text-white bg-primary-600 hover:bg-primary-800 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:focus:ring-primary-900 my-4">Back to Homepage</a>
                </div>
            </div>
        </section>
    @endif

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll("aside").forEach(aside => aside.style.display = "none");

                document.querySelector('.fi-topbar:has(nav)').style.display = "none";
            });
        </script>
    @endpush
</x-filament-panels::page>
