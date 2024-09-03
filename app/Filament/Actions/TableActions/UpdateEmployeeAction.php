<?php

namespace App\Filament\Actions\TableActions;

use App\Filament\Superuser\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\Enrollment;
use App\Models\Office;
use App\Models\Scanner;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Actions\Action;

class UpdateEmployeeAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->name ??= 'update-employee-enrollment';

        $this->requiresConfirmation();

        $this->label('Update');

        $this->icon('heroicon-o-pencil-square');

        $this->modalIcon('heroicon-o-pencil-square');

        $this->modalHeading('Update employee');

        $this->modalDescription(null);

        $this->successNotificationTitle('Employee updated');

        $this->slideOver();

        $this->modalWidth('2xl');

        $this->fillForm(function (Employee $record) {
            return [
                ...$record->toArray(),
                'deployments' => $record->deployments()->withoutGlobalScopes()->get()->mapWithKeys(function ($deployment) {
                    return [
                        $deployment->id => [
                            'id' => $deployment->id,
                            'current' => $deployment->current,
                            'active' => $deployment->active,
                            'office_id' => $deployment->office_id,
                            'supervisor_id' => $deployment->supervisor_id,
                        ],
                    ];
                })->values()->all(),
                'enrollments' => $record->enrollments()->withoutGlobalScopes()->get()->mapWithKeys(function ($enrollment) {
                    return [
                        $enrollment->id => [
                            'id' => $enrollment->id,
                            'device' => $enrollment->device,
                            'scanner_id' => $enrollment->scanner_id,
                            'active' => $enrollment->active,
                            'device' => $enrollment->scanner_id,
                            'uid' => $enrollment->uid,
                        ],
                    ];
                })->values()->all(),
            ];
        });

        $this->form([
            Tabs::make()
                ->contained(false)
                ->tabs([
                    Tab::make('Employee')
                        ->schema(EmployeeResource::formSchema(compact: true)),
                    Tab::make('Offices')
                        ->schema([
                            Section::make('Deployments')
                                ->compact()
                                ->schema([
                                    Repeater::make('deployments')
                                        ->hiddenLabel()
                                        ->itemLabel(fn (array $state) => Office::select('code')->find($state['office_id'])?->code)
                                        ->reorderable(false)
                                        ->schema([
                                            Group::make()
                                                ->columns(2)
                                                ->schema([
                                                    Toggle::make('current')
                                                        ->default(false)
                                                        ->required()
                                                        ->inline(false)
                                                        ->distinct(),
                                                    Toggle::make('active')
                                                        ->default(true)
                                                        ->required()
                                                        ->inline(false),
                                                ]),
                                            Select::make('office_id')
                                                ->label('Office')
                                                ->options(function (?string $state) {
                                                    $offices = Office::take(10)->orderBy('name')->pluck('name', 'id');

                                                    return $state
                                                        ? $offices->prepend(Office::withoutGlobalScopes()->find($state)?->name, $state)
                                                        : $offices;
                                                })
                                                ->getSearchResultsUsing(function (string $search) {
                                                    return Office::orderBy('name')
                                                        ->where('name', 'ilike', "%$search%")
                                                        ->orWhere('code', 'ilike', "%$search%")
                                                        ->pluck('name', 'id');
                                                })
                                                ->reactive()
                                                ->searchable()
                                                ->required()
                                                ->exists('offices', 'id')
                                                ->distinct()
                                                ->afterStateUpdated(fn (Set $set) => $set('supervisor_id', null)),
                                            Select::make('supervisor_id')
                                                ->label('Supervisor')
                                                ->options(function (Employee $record, Get $get, ?string $state) {
                                                    $office = Office::withoutGlobalScopes()->find($get('office_id'));

                                                    $employees = Employee::query()
                                                        ->whereNotIn('employees.id', [$record->id, $office?->head?->id])
                                                        ->whereHas('offices', function ($query) use ($office, $get) {
                                                            $query->where('offices.id', $get('office_id'))
                                                                ->where('active', true);
                                                        })
                                                        ->take(25)
                                                        ->reorder()
                                                        ->orderBy('name')
                                                        ->pluck('name', 'id');

                                                    return $state
                                                        ? $employees->prepend(Employee::find($state)?->name, $state)
                                                        : $employees;
                                                })
                                                ->getSearchResultsUsing(function (Employee $record, Get $get, ?string $search) {
                                                    $office = Office::withoutGlobalScopes()->find($get('office_id'));

                                                    return Employee::query()
                                                        ->where(function ($query) use ($search) {
                                                            $query->where('name', 'ilike', "%$search%")
                                                                ->orWhere('full_name', 'ilike', "%$search%");
                                                        })
                                                        ->whereNotIn('employees.id', [$record->id, $office->head?->id])
                                                        ->whereHas('offices', function ($query) use ($office) {
                                                            $query->where('offices.id', $office->id)
                                                                ->where('active', true);
                                                        })
                                                        ->take(25)
                                                        ->reorder()
                                                        ->orderBy('name')
                                                        ->pluck('name', 'id');
                                                })
                                                ->reactive()
                                                ->searchable()
                                                ->exists('employees', 'id')
                                                ->rule(fn (Get $get) => function ($attribute, $value, $fail) use ($get) {
                                                    if (
                                                        Employee::find($value)
                                                            ->offices()
                                                            ->where('offices.id', $get('office_id'))
                                                            ->doesntExist()
                                                    ) {
                                                        $fail('Selected employee is invalid.');
                                                    }
                                                }),
                                        ]),
                                ]),
                        ]),
                    Tab::make('scanners')
                        ->schema([
                            Section::make('Enrollments')
                                ->compact()
                                ->schema([
                                    Repeater::make('enrollments')
                                        ->hiddenLabel()
                                        ->itemLabel(fn (array $state) => Scanner::select('name')->find($state['scanner_id'])?->name)
                                        ->reorderable(false)
                                        ->columns(5)
                                        ->schema([
                                            Hidden::make('device'),
                                            Select::make('scanner_id')
                                                ->label('Scanner')
                                                ->options(Scanner::orderBy('priority', 'desc')->orderBy('name')->pluck('name', 'id'))
                                                ->reactive()
                                                ->searchable()
                                                ->required()
                                                ->exists('scanners', 'id')
                                                ->distinct()
                                                ->columnSpan(2)
                                                ->afterStateUpdated(function (Set $set, string $state) {
                                                    $scanner = Scanner::find($state);

                                                    $set('device', $scanner->device);
                                                }),
                                            TextInput::make('uid')
                                                ->label('UID')
                                                ->markAsRequired()
                                                ->rule('required')
                                                ->maxLength(16)
                                                ->columnSpan(2)
                                                ->rule(fn (Get $get) => function ($attribute, $value, $fail) use ($get) {
                                                    if (
                                                        Enrollment::query()
                                                            ->where('uid', $value)
                                                            ->where('scanner_id', $get('scanner_id'))
                                                            ->whereNot('id', $get('id'))
                                                            ->exists()
                                                    ) {
                                                        $fail('The UID has already been taken.');
                                                    }
                                                }),
                                            Toggle::make('active')
                                                ->required()
                                                ->inline(false),
                                        ]),
                                ]),
                        ])
                ]),
        ]);

        $this->action(function (Employee $record, array $data) {
            $record->fill($data)->save();

            $record->scanners()
                ->withoutGlobalScopes()
                ->sync(
                    collect($data['enrollments'])
                        ->mapWithKeys(function ($enrollment) {
                            return [
                                $enrollment['scanner_id'] => [
                                    'device' => $enrollment['device'],
                                    'active' => $enrollment['active'],
                                    'uid' => $enrollment['uid'],
                                ],
                            ];
                        })
                );

            $record->offices()
                ->withoutGlobalScopes()
                ->sync(
                    collect($data['deployments'])
                        ->mapWithKeys(function ($deployment) {
                            return [
                                $deployment['office_id'] => [
                                    'supervisor_id' => $deployment['supervisor_id'],
                                    'active' => $deployment['active'],
                                    'current' => $deployment['current'],
                                ],
                            ];
                        })
                );

            $this->sendSuccessNotification();
        });
    }
}
