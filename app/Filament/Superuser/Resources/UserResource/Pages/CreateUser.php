<?php

namespace App\Filament\Superuser\Resources\UserResource\Pages;

use App\Filament\Superuser\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
