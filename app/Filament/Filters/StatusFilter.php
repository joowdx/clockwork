<?php

namespace App\Filament\Filters;

use App\Enums\EmploymentStatus;
use App\Enums\EmploymentSubstatus;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Illuminate\Database\Eloquent\Builder;

class StatusFilter extends Filter
{
    protected ?string $relationship = null;

    public static function make(?string $name = null): static
    {
        $filterClass = static::class;

        $name ??= 'status-filter';

        $static = app($filterClass, ['name' => $name]);

        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->form([
            Select::make('status')
                ->options(EmploymentStatus::class)
                ->placeholder('All')
                ->multiple()
                ->searchable(),
            Select::make('substatus')
                ->visible(function (callable $get) {
                    $visibleOn = [
                        EmploymentStatus::CONTRACTUAL->value,
                    ];

                    return count(array_diff($visibleOn, $get('status') ?? [])) < count($visibleOn);
                })
                ->options(EmploymentSubstatus::class)
                ->placeholder('All')
                ->multiple()
                ->searchable(),
        ]);

        $this->query(function (Builder $query, array $data) {
            if (! isset($data['status'])) {
                return;
            }

            $filter = function (Builder $query) use ($data) {
                $query->when(
                    in_array(EmploymentStatus::INTERNSHIP->value, $data['status']),
                    fn ($query) => $query->withoutGlobalScope('excludeInterns'),
                );

                $query->when(
                    $data['status'],
                    fn ($query) => $query->whereIn('status', $data['status'])
                );

                $query->when(
                    $data['substatus'],
                    fn ($query) => $query->whereIn('substatus', $data['substatus'])
                );
            };

            $query->when($this->relationship, fn ($query) => $query->whereHas($this->relationship, $filter), $filter);
        });

        $this->indicateUsing(function (array $data) {
            $indicators = [];

            if (isset($data['status']) && count($data['status'])) {
                $statuses = collect($data['status'])
                    ->map(fn ($status) => EmploymentStatus::tryFrom($status)?->getLabel());

                $indicators[] = Indicator::make('Status: '.$statuses->join(', '))->removeField('status');
            }

            if (isset($data['substatus']) && count($data['substatus'])) {
                $substatuses = collect($data['substatus'])
                    ->map(fn ($status) => EmploymentSubstatus::tryFrom($status)->getLabel());

                $indicators[] = Indicator::make('Substatus: '.$substatuses->join(', '))->removeField('substatus');
            }

            return $indicators;
        });
    }

    public function relationship(?string $relationship): static
    {
        $this->relationship = $relationship;

        return $this;
    }
}
