<?php

namespace App\Filament\Filters;

use App\Models\Scopes\ActiveScope;
use Closure;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;

class ActiveFilter extends TernaryFilter
{
    protected ?string $modelRelationship = null;

    protected string|Closure|null $trueLabel = null;

    protected string|Closure|null $falseLabel = null;

    public static function getDefaultName(): ?string
    {
        return 'active-filter';
    }

    protected function setup(): void
    {
        parent::setUp();

        $this->native(false);

        $this->label('Active records');

        $this->placeholder('Without inactive records');

        $this->baseQuery(fn (Builder $query) => $query->when(
            $this->modelRelationship,
            fn ($query) => $query->whereHas($this->modelRelationship, fn ($query) => $query->withoutGlobalScope(ActiveScope::class)),
            fn ($query) => $query->withoutGlobalScope(ActiveScope::class),
        ));

        $this->trueLabel('With inactive records');

        $this->falseLabel('Only inactive records');

        $this->queries(
            true: fn ($query) => $query->when(
                $this->modelRelationship,
                fn ($query) => $query->whereHas($this->modelRelationship, fn ($query) => $query->withInactive()),
                fn ($query) => $query->withInactive(),
            ),
            false: fn ($query) => $query->when(
                $this->modelRelationship,
                fn ($query) => $query->whereHas($this->modelRelationship, fn ($query) => $query->onlyInactive()),
                fn ($query) => $query->onlyInactive(),
            ),
            blank: fn ($query) => $query->when(
                $this->modelRelationship,
                fn ($query) => $query->whereHas($this->modelRelationship, fn ($query) => $query->withoutInactive()),
                fn ($query) => $query->withoutInactive(),
            ),
        );
    }

    public function modelRelationship(?string $modelRelationship): static
    {
        $this->modelRelationship = $modelRelationship;

        return $this;
    }
}
