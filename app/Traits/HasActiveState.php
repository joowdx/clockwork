<?php

namespace App\Traits;

use App\Models\Scopes\ActiveScope;

trait HasActiveState
{
    /**
     * Boot the soft deleting trait for a model.
     *
     * @return void
     */
    public static function bootHasActiveState()
    {
        static::addGlobalScope(new ActiveScope);
    }
}
