<?php

namespace App\Filament\Superuser\Resources\EmployeeResource\RelationManagers;

use App\Filament\Filters\ActiveFilter;
use App\Models\Enrollment;
use App\Models\Scanner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class ScannersRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    protected static ?string $title = 'Scanner Enrollment';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('scanner_id')
                    ->relationship('scanner', 'name')
                    ->preload()
                    ->searchable()
                    ->disabledOn('edit')
                    ->required()
                    ->columnSpanFull()
                    ->validationMessages(['unique' => 'Employee has already been enrolled to this scanner.'])
                    ->rules([
                        fn (?Enrollment $record) => Rule::unique('enrollment', 'scanner_id')
                            ->where('employee_id', $this->ownerRecord->id)
                            ->ignore($record?->id, 'id'),
                    ]),
                Forms\Components\TextInput::make('uid')
                    ->markAsRequired()
                    ->label('UID')
                    ->rules('required')
                    ->maxLength(255)
                    ->validationAttribute('uid')
                    ->rules([
                        fn (Forms\Get $get, ?Enrollment $record) => Rule::unique('enrollment', 'uid')
                            ->where('scanner_id', $record?->scanner_id ?? $get('scanner_id'))
                            ->ignore($record?->id, 'id'),
                    ]),
                Forms\Components\ToggleButtons::make('active')
                    ->boolean()
                    ->inline()
                    ->grouped()
                    ->required()
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereHas('scanner', fn ($q) => $q->whereActive(1)))
            ->columns([
                Tables\Columns\TextColumn::make('scanner.name')
                    ->label('Name')
                    ->placeholder(fn ($record) => $record->id)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('uid')
                    ->label('UID')
                    ->sortable(query: fn ($query, $direction) => $query->orderByRaw("CAST(uid as UNSIGNED) $direction"))
                    ->searchable(query: fn ($query, $search) => $query->where('uid', $search)),
                Tables\Columns\TextColumn::make('active')
                    ->getStateUsing(fn ($record) => $record->active ? 'Yes' : 'No')
                    ->icon(fn ($record) => $record->active ? 'heroicon-o-check' : 'heroicon-o-no-symbol')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                ActiveFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->createAnother(false)
                    ->slideOver()
                    ->modalWidth('xl'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->modalWidth('xl'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-x-circle')
                    ->modalIcon('heroicon-o-shield-exclamation'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->icon('heroicon-o-x-circle')
                        ->modalIcon('heroicon-o-shield-exclamation'),
                ]),
            ])
            ->defaultSort(function ($query) {
                $query->orderBy(
                    Scanner::select('active')
                        ->whereColumn('scanners.id', 'enrollment.id')
                        ->limit(1),
                    'asc'
                );

                $query->orderBy(
                    Scanner::select('name')
                        ->whereColumn('scanners.id', 'enrollment.id')
                        ->limit(1),
                    'asc'
                );
            })
            ->recordAction(null);
    }
}
