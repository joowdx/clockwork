<?php

namespace App\Filament\Secretary\Resources;

use App\Filament\Actions\TableActions\BulkAction\DeleteTimesheetAction;
use App\Filament\Actions\TableActions\BulkAction\ExportTimesheetAction;
use App\Filament\Actions\TableActions\BulkAction\ExportTransmittalAction;
use App\Filament\Actions\TableActions\BulkAction\GenerateTimesheetAction;
use App\Filament\Actions\TableActions\BulkAction\ViewTimesheetAction;
use App\Filament\Filters\ActiveFilter;
use App\Filament\Filters\StatusFilter;
use App\Filament\Secretary\Resources\TimesheetResource\Pages;
use App\Models\Employee;
use App\Models\Office;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TimesheetResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'gmdi-document-scanner-o';

    protected static ?string $navigationLabel = 'Timesheets';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('offices.code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->toggleable()
                    ->limit(24)
                    ->getStateUsing(function (Employee $employee): string {
                        return str($employee->status?->value)
                            ->title()
                            ->when($employee->substatus?->value, function ($status) use ($employee) {
                                return $status->append(" ({$employee->substatus->value})")->replace('_', '-')->title();
                            });
                    }),
            ])
            ->filters([
                ActiveFilter::make(),
                StatusFilter::make(),
                // SelectFilter::make('offices')
                //     ->multiple()
                //     ->preload()
                    // ->relationship('offices', 'name', function ($query) {
                    //     $query->whereIn('offices.id', auth()->user()->offices->pluck('id'));

                    //     $query->orWhereHas('employees', function ($query) {
                    //         $query->whereHas('scanners', function (Builder $query) {
                    //             $query->whereIn('scanners.id', auth()->user()->scanners->pluck('id')->toArray());
                    //         });
                    //     });
                    // }),
                Filter::make('offices')
                    ->form([
                        Select::make('offices')
                            ->options(
                                Office::query()
                                    ->where(function ($query) {
                                        $query->whereIn('id', auth()->user()->offices->pluck('id'));

                                        $query->orWhereHas('employees', function ($query) {
                                            $query->whereHas('scanners', function (Builder $query) {
                                                $query->whereIn('scanners.id', auth()->user()->scanners->pluck('id')->toArray());
                                            });
                                        });
                                    })
                                    ->pluck('code', 'id')
                            )
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                $query = Office::query();

                                $query->where(function ($query) {
                                    $query->whereIn('id', auth()->user()->offices->pluck('id'));

                                    $query->orWhereHas('employees', function ($query) {
                                        $query->whereHas('scanners', function (Builder $query) {
                                            $query->whereIn('scanners.id', auth()->user()->scanners->pluck('id')->toArray());
                                        });
                                    });
                                });

                                $query->where(function ($query) use ($search) {
                                    $query->where('code', 'ilike', "%{$search}%")
                                        ->orWhere('name', 'ilike', "%{$search}%");
                                });

                                return $query->pluck('code', 'id');
                            })
                            ->preload()
                            ->multiple(),
                    ])
                    ->query(function(Builder $query, array $data) {
                        $query->when($data['offices'], function ($query) use ($data) {
                            $query->whereHas('offices', function ($query) use ($data) {
                                $query->whereIn('offices.id', $data['offices']);
                                $query->where('deployment.active', true);
                            });

                        });
                    })
                    ->indicateUsing(function (array $data) {
                        if (empty($data['offices'])) {
                            return null;
                        }

                        $offices = Office::select('code')
                            ->orderBy('code')
                            ->find($data['offices'])
                            ->pluck('code');

                        return 'Offices: ' . $offices->join(', ');
                    }),
                SelectFilter::make('groups')
                    ->relationship(
                        'groups',
                        'name',
                        fn ($query) => $query->whereHas('employees', function ($query) {
                            $query->whereHas('offices', function ($query) {
                                $query->whereIn('offices.id', auth()->user()->offices->pluck('id'));
                            })
                                ->orWhereHas('scanners', function (Builder $query) {
                                    $query->whereIn('scanners.id', auth()->user()->scanners->pluck('id')->toArray());
                                });
                        })
                    )
                    ->multiple()
                    ->preload(),
            ])
            ->bulkActions([
                ViewTimesheetAction::make(listing: true),
                ViewTimesheetAction::make()
                    ->label('View'),
                Tables\Actions\BulkActionGroup::make([
                    ExportTimesheetAction::make()
                        ->label('Timesheet'),
                    ExportTransmittalAction::make()
                        ->label('Transmittal'),
                ])
                    ->label('Export')
                    ->icon('heroicon-o-document-arrow-down'),
                GenerateTimesheetAction::make()
                    ->label('Generate'),
                DeleteTimesheetAction::make('delete'),
            ])
            ->recordAction(null)
            ->recordUrl(null)
            ->defaultSort('name', 'asc')
            ->deselectAllRecordsWhenFiltered(false);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTimesheets::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function ($query) {
                $query->whereHas('offices', function (Builder $query) {
                    $query->whereIn('offices.id', auth()->user()->offices->pluck('id')->toArray());
                })
                    ->orWhereHas('scanners', function (Builder $query) {
                        $query->whereIn('scanners.id', auth()->user()->scanners->pluck('id')->toArray());
                    });
            });
    }
}
