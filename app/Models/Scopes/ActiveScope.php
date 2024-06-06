<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ActiveScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where($model->getTable().'.active', true);
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @return void
     */
    public function extend(Builder $builder)
    {
        $builder->macro('withInactive', function (Builder $builder): Builder {
            return $builder->withoutGlobalScope($this);
        });

        $builder->macro('onlyInactive', function (Builder $builder): Builder {
            return $builder->withoutGlobalScope($this)->where($builder->getModel()->getTable().'.active', false);
        });

        $builder->macro('withoutInactive', function (Builder $builder): Builder {
            return $builder->withoutGlobalScope($this)->where($builder->getModel()->getTable().'.active', true);
        });
    }
}
