<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface UserRepository extends Repository
{
    public function updatePassword(Model $user, array $password): void;

    public function updateProfile(Model $user, array $info): void;

    public function removeProfilePhoto(Model $user): void;

    public function sortByUsername(string $direction): self;

    public function sessions(Model $user): Collection;

    public function deleteOtherSessionRecords(Model $user): void;
}
