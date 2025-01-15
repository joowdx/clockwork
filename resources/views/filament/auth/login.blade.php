@extends('filament.auth.base')

@section('subheading')
    {{ $this->homeAction }}
@endsection

@section('content')
    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

    <x-filament-panels::form wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    @php($providers = config('services.oauth_providers'))

    @if ($providers)
        <div class="relative flex items-center justify-center text-center">
            <div class="absolute w-full h-px border-t border-gray-200"></div>
            <p class="relative inline-block p-2 text-sm font-medium text-gray-500 bg-white rounded-full dark:bg-gray-800 dark:text-gray-100">
                Or log in via
            </p>
        </div>

        <div class="grid grid-cols-2 gap-3">
            @foreach ($providers as $provider)
                <x-filament::button
                    :tooltip="ucfirst($provider)"
                    :icon="'fab-'.$provider"
                    color="gray"
                    outlined
                    wire:click="socialite('{{ $provider }}')"
                    wire:target="socialite('{{ $provider }}'"
                >
                    {{ ucfirst($provider) }}
                </x-filament::button>
            @endforeach
        </div>
    @endif

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}
@endsection

@section('footer')
    @php($ua = settings('ua'))

    @php($pp = settings('pp'))

    @if ($ua || $pp)
        <div class="text-sm text-center text-gray-500">
            <p>
                By continuing, you agree to our
                @if ($pp)
                    <x-filament::link href="{{ route('filament.legal.pages.privacy-policy') }}">
                        Privacy Policy
                    </x-filament::link>
                @endif

                @if ($ua && $pp)
                    &amp;
                @endif

                @if ($ua)
                    <x-filament::link href="{{ route('filament.legal.pages.user-agreement') }}">
                        User Agreement
                    </x-filament::link>
                @endif
            </p>
        </div>
    @endif
@endsection
