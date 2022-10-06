<?php

namespace Database\Seeders;

use App\Models\Scanner;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ScannerSeeder extends Seeder
{

    const SCANNERS = [
        'capitol_1',
        'capitol_2',
        'coliseum',
        'coliseum_1',
        'coliseum_2',
        'coliseum_3',
        'coliseum_4',
        'opag',
        'opag_coliseum',
        'pacco',
        'passo',
        'pbo',
        'pbo_coliseum',
        'peo',
        'peo_coliseum',
        'pto',
        'pto_coliseum',
        'pho',
        'pvo',
        'penro',
        'ppdo',
        'pgso_1',
        'pgso_2',
        'pgo_admin',
        'pgo_pdrrmo',
        'pgo_picto',
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
