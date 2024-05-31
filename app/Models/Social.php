<?php

namespace App\Models;

use DutchCodingCompany\FilamentSocialite\Models\SocialiteUser;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Social extends SocialiteUser
{
    use HasUlids;
}
