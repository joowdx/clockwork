<?php

namespace App\Filament\Validation\Pages;

use App\Models\Export;
use Filament\Actions\Action;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Pages\Dashboard;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class Home extends Dashboard
{
    public ?Export $export = null;

    public ?string $url = null;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.validation.pages.home';

    protected static ?string $navigationLabel = 'Home';

    protected ?string $heading = 'Validation';

    public function mount(Request $request): void
    {
        $this->export = Export::find($request->get('q'));
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::ExtraLarge;
    }

    public function getHeading(): string|Htmlable
    {
        return 'Validation';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return @$this->export->exportable?->employee?->name;
    }

    public function getHeaderActions(): array
    {
        return is_null($this->export) ? [] : [
            Action::make('go_home')
                ->url('/'),
            Action::make('download_file')
                ->color('gray')
                ->action(fn () => response()->streamDownload(fn () => print ($this->export->content), $this->export->filename)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->export)
            ->columns(3)
            ->schema([
                TextEntry::make('details.certification.at')
                    ->label('Certified by employee')
                    ->since()
                    ->dateTimeTooltip(),
                TextEntry::make('details.verification.supervisor.at')
                    ->label('Verified by supervisor')
                    ->placeholder(str('<i>Not yet</i>')->toHtmlString())
                    ->since()
                    ->dateTimeTooltip(),
                TextEntry::make('details.verification.head.at')
                    ->label('Verified by head')
                    ->placeholder(str('<i>Not yet</i>')->toHtmlString())
                    ->since()
                    ->dateTimeTooltip(),
                Group::make([
                    TextEntry::make('exportable')
                        ->hiddenLabel()
                        ->alignCenter()
                        ->formatStateUsing(function (): View {
                            return view('filament.validation.pages.preview', [
                                'timesheets' => [$this->export->exportable->setSpan($this->export->details->period)],
                                'preview' => true,
                            ]);
                        }),
                    TextEntry::make('digest')
                        ->fontFamily(FontFamily::Mono)
                        ->extraAttributes(['style' => 'word-break:break-all;'])
                        ->copyable()
                        ->copyMessage('Copied!')
                        ->copyMessageDuration(1500),
                ])->columnSpan(3),
            ]);
    }
}
