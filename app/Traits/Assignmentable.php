<?php

namespace App\Traits;

use App\Models\Assignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait Assignmentable
{
    public function users(): MorphToMany
    {
        return $this->morphToMany(User::class, 'assignable', Assignment::class)
            ->using(Assignment::class)
            ->withPivot('active');
    }

    public function assignees(): MorphMany
    {
        return $this->morphMany(Assignment::class, 'assignable');
    }
}
