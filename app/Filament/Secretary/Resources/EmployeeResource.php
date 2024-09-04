<?php

namespace App\Filament\Secretary\Resources;

use App\Filament\Filters\ActiveFilter;
use App\Filament\Filters\StatusFilter;
use App\Filament\Secretary\Resources\EmployeeResource\Pages;
use App\Filament\Superuser\Resources\EmployeeResource as SuperuserEmployeeResource;
use App\Filament\Superuser\Resources\EmployeeResource\RelationManagers\OfficesRelationManager;
use App\Filament\Superuser\Resources\EmployeeResource\RelationManagers\ScannersRelationManager;
use App\Models\Employee;
use App\Models\Office;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'gmdi-badge-o';

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(SuperuserEmployeeResource::formSchema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('offices.code')
                    ->formatStateUsing(fn (Employee $record) => $record->offices->filter(fn ($office) => $office->pivot->active)->pluck('code')->join(', ')),
                Tables\Columns\TextColumn::make('status'),
            ])
            ->filters([
                ActiveFilter::make(),
                StatusFilter::make(),
                Tables\Filters\Filter::make('offices')
                    ->form([
                        Forms\Components\Select::make('offices')
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
                    ->query(function (Builder $query, array $data) {
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

                        return 'Offices: '.$offices->join(', ');
                    }),
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            OfficesRelationManager::class,
            ScannersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->where(function (Builder $query) {
                $query->whereHas('offices', function (Builder $query) {
                    $query->whereIn('offices.id', auth()->user()->offices->pluck('id'));
                });

                $query->whereHas('scanners', function (Builder $query) {
                    $query->whereIn('scanners.id', auth()->user()->scanners->pluck('id'));
                });
            });
    }
}
