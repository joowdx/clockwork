<?php

namespace App\Filament\Secretary\Resources\TimesheetResource\Pages;

use App\Filament\Secretary\Resources\TimesheetResource;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;

class ListTimesheets extends ListRecords
{
    use HasFiltersForm;

    protected static string $resource = TimesheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            FilterAction::make()
                ->label('Options')
                ->icon('heroicon-o-adjustments-horizontal')
                ->modalHeading('Option')
                ->slideOver(false)
                ->modalDescription(function () {
                    $html = <<<'HTML'
                        <span class="text-sm text-custom-600 dark:text-custom-400" style="--c-400:var(--warning-400);--c-600:var(--warning-600);">
                            Note: You can still override these settings when generating the timesheet.
                        </span>
                    HTML;

                    return str($html)->toHtmlString();
                })
                ->form([
                    TextInput::make('month')
                        ->live()
                        ->default(today()->day > 15 ? today()->startOfMonth()->format('Y-m') : today()->subMonth()->format('Y-m'))
                        ->type('month')
                        ->required(),
                    Select::make('period')
                        ->default(today()->day > 15 ? '1st' : 'full')
                        ->required()
                        ->live()
                        ->options([
                            'full' => 'Full month',
                            '1st' => 'First half',
                            '2nd' => 'Second half',
                            'regular' => 'Regular days',
                            'overtime' => 'Overtime work',
                            'custom' => 'Custom range',
                        ])
                        ->disableOptionWhen(function (Get $get, ?string $value) {
                            if ($get('format') === 'csc') {
                                return false;
                            }

                            return match ($value) {
                                'full', '1st', '2nd', 'custom' => false,
                                default => true,
                            };
                        })
                        ->dehydrateStateUsing(function (Get $get, ?string $state) {
                            if ($state !== 'custom') {
                                return $state;
                            }

                            return $state.'|'.date('d', strtotime($get('from'))).'-'.date('d', strtotime($get('to')));
                        })
                        ->in(fn (Select $component): array => array_keys($component->getEnabledOptions())),
                    DatePicker::make('from')
                        ->label('Start')
                        ->visible(fn (Get $get) => $get('period') === 'custom')
                        ->default(today()->day > 15 ? today()->startOfMonth()->format('Y-m-d') : today()->subMonth()->startOfMonth()->format('Y-m-d'))
                        ->validationAttribute('start')
                        ->minDate(fn (Get $get) => $get('month').'-01')
                        ->maxDate(fn (Get $get) => Carbon::parse($get('month'))->endOfMonth())
                        ->required()
                        ->dehydrated(false)
                        ->beforeOrEqual('to'),
                    DatePicker::make('to')
                        ->label('End')
                        ->visible(fn (Get $get) => $get('period') === 'custom')
                        ->default(today()->day > 15 ? today()->endOfMonth()->format('Y-m-d') : today()->subMonth()->setDay(15)->format('Y-m-d'))
                        ->validationAttribute('end')
                        ->minDate(fn (Get $get) => $get('month').'-01')
                        ->maxDate(fn (Get $get) => Carbon::parse($get('month'))->endOfMonth())
                        ->required()
                        ->dehydrated(false)
                        ->afterOrEqual('from'),
                    Select::make('format')
                        ->live()
                        ->placeholder('Print format')
                        ->default('csc')
                        ->required()
                        ->options(['default' => 'Default format', 'csc' => 'CSC format']),
                    Select::make('size')
                        ->live()
                        ->placeholder('Paper Size')
                        ->default('folio')
                        ->required()
                        ->options([
                            'a4' => 'A4 (210mm x 297mm)',
                            'letter' => 'Letter (216mm x 279mm)',
                            'folio' => 'Folio (216mm x 330mm)',
                            'legal' => 'Legal (216mm x 356mm)',
                        ]),
                    Checkbox::make('electronic_signature')
                        ->hintIcon('heroicon-o-check-badge')
                        ->hintIconTooltip('Electronically sign the document. This does not provide security against tampering.')
                        ->live()
                        ->afterStateUpdated(fn ($get, $set, $state) => $set('digital_signature', $state ? $get('digital_signature') : false))
                        ->rule(fn () => function ($attribute, $value, $fail) {
                            if ($value && ! auth()->user()->signature) {
                                $fail('Configure your electronic signature first');
                            }
                        }),
                ]),
        ];
    }
}
