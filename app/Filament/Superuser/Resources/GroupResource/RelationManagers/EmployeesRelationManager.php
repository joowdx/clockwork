<?php

namespace App\Filament\Superuser\Resources\GroupResource\RelationManagers;

use App\Filament\Filters\ActiveFilter;
use App\Models\Employee;
use App\Models\Member;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EmployeesRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    protected static ?string $title = 'Employee members';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('employee_id')
                    ->relationship('employee', 'name', function ($query) {
                        $admin = Filament::getCurrentPanel()->getId() === 'superuser';

                        if ($admin) {
                            return;
                        }

                        $query->where(function (Builder $query) {
                            $query->orWhereHas('offices', function (Builder $query) {
                                $query->whereIn('offices.id', Auth::user()->offices->pluck('id'));
                            });

                            $query->orWhereHas('scanners', function (Builder $query) {
                                $query->whereIn('scanners.id', Auth::user()->scanners->pluck('id'));
                            });
                        });
                    })
                    ->searchable()
                    ->preload()
                    ->disabledOn('edit')
                    ->required()
                    ->columnSpanFull()
                    ->validationMessages(['unique' => 'Employee is already a member of this group.'])
                    ->rules([
                        fn (?Member $record) => Rule::unique('member', 'employee_id')
                            ->where('group_id', $this->ownerRecord->id)
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
            ->modifyQueryUsing(function (Builder $query) {
                $query->with(['employee.offices', 'employee.scanners']);

                if (Filament::getCurrentPanel()->getId() === 'secretary') {
                    $query->whereHas('employee', function ($query) {
                        $query->whereHas('scanners', function ($query) {
                            $query->whereIn('scanners.id', user()->scanners()->pluck('scanners.id'));
                        });

                        $query->orWhereHas('offices', function ($query) {
                            $query->whereIn('offices.id', user()->offices()->pluck('offices.id'));
                        });

                        $query->where('employees.active', true);
                    });
                } else {
                    $query->whereHas('employee');
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('employee.offices.code')
                    ->searchable()
                    ->formatStateUsing(function (Member $record) {
                        $offices = $record->employee->offices->map(function ($office) {
                            return str($office->code)
                                ->when($office->pivot->current, function ($code) {
                                    return <<<HTML
                                        <span class="text-sm text-custom-600 dark:text-custom-400" style="--c-400:var(--primary-400);--c-600:var(--primary-600);">$code</span>
                                    HTML;
                                });
                        })->join(', ');

                        return str($offices)->toHtmlString();
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->toggleable()
                    ->getStateUsing(function (Member $record): string {
                        return str($record->employee->status?->value)
                            ->title()
                            ->when($record->employee->substatus?->value, function ($status) use ($record) {
                                return $status->append(" ({$record->employee->substatus->value})")->replace('_', '-')->title();
                            });
                    }),
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
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->modalWidth('xl')
                    ->visible(function (Member $record) {
                        $admin = Filament::getCurrentPanel()->getId() === 'superuser';

                        if ($admin) {
                            return true;
                        }

                        $user = user();

                        $offices = $user->offices->map(function ($office) {
                            return $office->id;
                        });

                        $scanners = $user->scanners->map(function ($scanner) {
                            return $scanner->id;
                        });

                        return $record->employee?->offices->some(fn ($office) => in_array($office->id, $offices->toArray())) ||
                            $record->employee?->scanners->some(fn ($scanner) => in_array($scanner->id, $scanners->toArray()));
                    }),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-x-circle')
                    ->modalIcon('heroicon-o-shield-exclamation')
                    ->visible(function (Member $record) {
                        $admin = Filament::getCurrentPanel()->getId() === 'superuser';

                        if ($admin) {
                            return true;
                        }

                        $user = user();

                        $offices = $user->offices->map(function ($office) {
                            return $office->id;
                        });

                        $scanners = $user->scanners->map(function ($scanner) {
                            return $scanner->id;
                        });

                        return $record->employee?->offices->some(fn ($office) => in_array($office->id, $offices->toArray())) ||
                            $record->employee?->scanners->some(fn ($scanner) => in_array($scanner->id, $scanners->toArray()));
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->icon('heroicon-o-x-circle')
                        ->modalIcon('heroicon-o-shield-exclamation'),
                ]),
            ])
            ->defaultSort(function (Builder $query) {
                $query->orderBy(
                    Employee::select('status')
                        ->whereColumn('employees.id', 'member.employee_id')
                        ->limit(1),
                    'desc'
                );

                $query->orderBy(
                    Employee::select('substatus')
                        ->whereColumn('employees.id', 'member.employee_id')
                        ->limit(1),
                );

                $query->orderBy(
                    Employee::select('name')
                        ->whereColumn('employees.id', 'member.employee_id')
                        ->limit(1),
                );
            })
            ->recordAction(null);
    }
}
