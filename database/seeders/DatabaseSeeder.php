<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Scanner;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        Scanner::factory(1)->hasAttached(Employee::factory(1), ['uid' => rand()])->has(User::factory()->jude())->create();

        Scanner::factory(1)->hasAttached(Employee::factory(1), ['uid' => rand()])->has(User::factory()->gene())->create();
    }
}
