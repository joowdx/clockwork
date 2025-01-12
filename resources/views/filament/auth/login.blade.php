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

@section('footer')
    @php($ua = settings('ua'))
    
    @php($pp = settings('pp'))

    @if ($ua || $pp)
        <div class="text-sm text-gray-500 text-center">
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