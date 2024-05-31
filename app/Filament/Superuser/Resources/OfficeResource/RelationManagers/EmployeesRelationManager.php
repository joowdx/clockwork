<?php

namespace App\Filament\Superuser\Resources\OfficeResource\RelationManagers;

use App\Filament\Filters\ActiveFilter;
use App\Models\Deployment;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;

class EmployeesRelationManager extends RelationManager
{
    protected static string $relationship = 'deployments';

    protected static ?string $title = 'Employee deployment';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('employee_id')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload()
                    ->disabledOn('edit')
                    ->required()
                    ->columnSpanFull()
                    ->validationMessages(['unique' => 'Employee is already deployed to this office.'])
                    ->rules([
                        fn (?Deployment $record) => Rule::unique('deployment', 'employee_id')
                            ->where('office_id', $this->ownerRecord->id)
                            ->ignore($record?->id, 'id'),
                    ]),
                Forms\Components\Select::make('supervisor_id')
                    ->relationship('supervisor', 'name', function ($query, $record) {
                        $query->whereHas('offices', function ($query) {
                            $query->where('offices.id', $this->ownerRecord->id)
                                ->where('active', true);
                        });
                    })
                    ->searchable()
                    ->preload()
                    ->columnSpanFull()
                    ->validationMessages(['unique' => 'Employee is already deployed to this office.'])
                    ->rules([
                        fn (?Deployment $record) => Rule::unique('deployment', 'office_id')
                            ->where('employee_id', $this->ownerRecord->id)
                            ->ignore($record?->id, 'id'),
                    ]),
                Forms\Components\ToggleButtons::make('active')
                    ->boolean()
                    ->inline()
                    ->grouped()
                    ->required()
                    ->default(true),
                Forms\Components\ToggleButtons::make('current')
                    ->hiddenOn('edit')
                    ->boolean()
                    ->inline()
                    ->grouped()
                    ->required()
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereHas('employee', fn ($q) => $q->whereActive(1)))
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('supervisor.name')
                    ->formatStateUsing(fn ($record) => $record->supervisor?->titled_name)
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('current')
                    ->getStateUsing(fn ($record) => $record->current ? 'Yes' : 'No')
                    ->icon(fn ($record) => $record->current ? 'heroicon-o-check' : 'heroicon-o-no-symbol')
                    ->toggleable(),
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
                    ->slideOver()
                    ->modalWidth('xl'),
            ])
            ->actions([
                Tables\Actions\Action::make('Current')
                    ->disabled(fn ($record) => $record->current)
                    ->requiresConfirmation()
                    ->icon('heroicon-o-check-badge')
                    ->modalIcon('heroicon-o-check-badge')
                    ->modalDescription('Set this deployment as the current office for this employee?')
                    ->action(function (Deployment $record) {
                        Deployment::where('employee_id', $record->employee_id)->update(['current' => false]);

                        $record->update(['current' => true]);
                    }),
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->modalWidth('xl'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-x-circle')
                    ->modalIcon('heroicon-o-shield-exclamation'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('Edit records')
                        ->groupedIcon('heroicon-m-pencil-square')
                        ->requiresConfirmation()
                        ->modalDescription('Leave blank to leave unchanged.')
                        ->modalIcon('heroicon-m-pencil-square')
                        ->form([
                            Forms\Components\Select::make('current')
                                ->boolean(),
                            Forms\Components\Select::make('supervisor_id')
                                ->relationship('supervisor', 'name', function ($query) {
                                    $query->whereNot('id', $this->ownerRecord->head?->id);

                                    $query->whereHas('offices', function ($query) {
                                        $query->where('office_id', $this->ownerRecord->id)
                                            ->where('active', true);
                                    });
                                })
                                ->searchable()
                                ->preload()
                                ->columnSpanFull()
                                ->placeholder('-')
                                ->validationMessages(['unique' => 'Employee is already deployed to this office.'])
                                ->hintAction(
                                    Action::make('Remove supervisor')
                                        ->icon('heroicon-o-x-circle')
                                        ->action(function (Forms\Set $set) {
                                            $set('supervisor_id', '<to-remove>');
                                        })
                                )
                                ->rules([
                                    fn (?Deployment $record) => Rule::unique('deployment', 'office_id')
                                        ->where('employee_id', $this->ownerRecord->id)
                                        ->ignore($record?->id, 'id'),
                                ]),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $data = array_filter($data);

                            $supervisor = isset($data['supervisor_id']) && $data['supervisor_id'] !== '<to-remove>' ? $data['supervisor_id'] : null;

                            $records->toQuery()->update($data);

                            if (isset($data['current']) && $data['current']) {
                                Deployment::whereIn('employee_id', $records->pluck('employee_id'))
                                    ->whereNot('office_id', $this->ownerRecord->id)
                                    ->update(['current' => false]);
                            }

                            if ($supervisor) {
                                $records->filter(fn ($record) => in_array($record->employee_id, [$supervisor, $this->ownerRecord->head?->id]))
                                    ->toQuery()
                                    ->update(['supervisor_id' => null]);
                            }
                        }),
                    Tables\Actions\DeleteBulkAction::make()
                        ->icon('heroicon-o-x-circle')
                        ->modalIcon('heroicon-o-shield-exclamation'),
                ]),
            ])
            ->recordAction(null);
    }
}
