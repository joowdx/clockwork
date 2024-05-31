<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface Auditable
{
    public function activities(): MorphMany;

    public function latestActivity(): MorphOne;
}
