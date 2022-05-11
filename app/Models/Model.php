<?php

namespace App\Models;

use App\Traits\HasUniversallyUniqueIdentifier;
use Awobaz\Compoships\Compoships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Main;

class Model extends Main
{
    use Compoships, HasFactory, HasUniversallyUniqueIdentifier;
}
