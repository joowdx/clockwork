<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Laravel\Sanctum\PersonalAccessToken;

class Token extends PersonalAccessToken
{
    use HasUlids;
}
