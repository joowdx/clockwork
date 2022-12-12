<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    const USERS = [
        'gene',
        'jude',
        'edwin',
        'rustell',
        'benz',
        'carl',
        'reymar',
        'mark',
        'ian',
        'merryloueshenarah',
        'klouie',
        'jefferson',
        'roderick',
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (self::USERS as $user) {
            User::factory()->{$user}()->create();
        }
    }
}
