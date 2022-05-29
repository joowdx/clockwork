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
        User::factory()->jude()->create();
        User::factory()->gene()->create();

        Employee::factory()->jude()->create();

        Scanner::factory()->coliseum_1()->create();
        Scanner::factory()->coliseum_2()->create();
        Scanner::factory()->coliseum_3()->create();
        Scanner::factory()->coliseum_4()->create();
        Scanner::factory()->capitol()->create();
        Scanner::factory()->pto()->create();
        Scanner::factory()->pto_coliseum()->create();
        Scanner::factory()->pbo()->create();
        Scanner::factory()->pbo_coliseum()->create();
        Scanner::factory()->peo()->create();
        Scanner::factory()->peo_coliseum()->create();
        Scanner::factory()->pho()->create();
        Scanner::factory()->pvo()->create();
        Scanner::factory()->pacco()->create();
        Scanner::factory()->passo()->create();
        Scanner::factory()->penro()->create();
        Scanner::factory()->ppdo()->create();
        Scanner::factory()->pgso_1()->create();
        Scanner::factory()->pgso_2()->create();
        Scanner::factory()->pgo_admin()->create();
        Scanner::factory()->pgo_pdrrmo()->create();
        Scanner::factory()->pgo_pio()->create();
        Scanner::factory()->pgo_bac()->create();
        Scanner::factory()->pgo_sbac()->create();
        Scanner::factory()->pgo_osp()->create();
        Scanner::factory()->pgo_csu()->create();

        User::first()->scanners()->sync(Scanner::all()->map->id);

        Employee::first()->scanners()->sync(Scanner::all()->mapWithKeys(fn ($s) => [$s->id => ['uid' => 1]]));
    }
}
