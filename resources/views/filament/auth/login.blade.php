@extends('filament.auth.base')

@section('content')
    @if (filament()->hasRegistration())
        <x-slot name="subheading">
            {{ __('filament-panels::pages/auth/login.actions.register.before') }}

            {{ $this->registerAction }}
        </x-slot>
    @endif

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

    <x-filament-panels::form wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    @php($ua = settings('ua'))
    @php($pp = settings('pp'))
    @if($ua || $pp)
        <div class="text-sm text-gray-500">
            <p class="text-center">
                By continuing, you agree to our
                @if($pp)
                <a href="{{ route('filament.legal.pages.privacy-policy') }}" target="_blank" class="text-blue-500 hover:underline">
                    Privacy Policy
                </a>
                @endif
                @if ($ua && $pp)
                    and
                @endif
                @if ($ua)
                    <a href="{{ route('filament.legal.pages.user-agreement') }}" target="_blank" class="text-blue-500 hover:underline">
                        User Agreement
                    </a>
                @endif
            </p>
        </div>
    @endif

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}
@endsection
