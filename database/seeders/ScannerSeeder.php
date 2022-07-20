<?php

namespace Database\Seeders;

use App\Models\Scanner;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ScannerSeeder extends Seeder
{

    const SCANNERS = [
        'coliseum_1',
        'coliseum_2',
        'coliseum_3',
        'capitol_1',
        'capitol_2',
        'pto',
        'pto_coliseum',
        'pbo',
        'pbo_coliseum',
        'peo',
        'peo_coliseum',
        'pho',
        'pvo',
        'pacco',
        'passo',
        'penro',
        'ppdo',
        'pgso_1',
        'pgso_2',
        'pgo_admin',
        'pgo_pdrrmo',
        'pgo_pio',
        'pgo_bac',
        'pgo_sbac',
        'pgo_osp',
        'pgo_csu',
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach(self::SCANNERS as $scanner) {
            Scanner::factory()->{$scanner}()->create();
        }
    }
}
