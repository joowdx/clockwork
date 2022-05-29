<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Scanner>
 */
class ScannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word()
        ];
    }

    public function coliseum_1()
    {
        return $this->state([
            'name' => 'coliseum-1',
            'attlog' => 'AF4C211360031_attlog',
            'remarks' => 'Shared with to all offices without dedicated scanners at the coliseum.',
        ]);
    }

    public function coliseum_2()
    {
        return $this->state([
            'name' => 'coliseum-2',
            'remarks' => 'Shared with to all offices without dedicated scanners at the coliseum.',
        ]);
    }

    public function coliseum_3()
    {
        return $this->state([
            'name' => 'coliseum-3',
            'remarks' => 'Shared with to all offices without dedicated scanners at the coliseum.',
        ]);
    }

    public function coliseum_4()
    {
        return $this->state([
            'name' => 'coliseum-4',
            'remarks' => 'Shared with PCO, PP, and PTDPO.'
        ]);
    }

    public function capitol()
    {
        return $this->state([
            'name' => 'capitol',
            'remarks' => 'Shared with PLO, PESO, PSWDO, PP, and PHRMO.',
        ]);
    }

    public function pacco()
    {
        return $this->state([
            'name' => 'pacco',
        ]);
    }

    public function passo()
    {
        return $this->state([
            'name' => 'passo',
        ]);
    }

    public function pto()
    {
        return $this->state([
            'name' => 'pto',
        ]);
    }

    public function pto_coliseum()
    {
        return $this->state([
            'name' => 'pto-coliseum',
        ]);
    }

    public function pbo()
    {
        return $this->state([
            'name' => 'pbo',
        ]);
    }

    public function pbo_coliseum()
    {
        return $this->state([
            'name' => 'pbo-coliseum',
        ]);
    }

    public function peo()
    {
        return $this->state([
            'name' => 'peo',
        ]);
    }

    public function peo_coliseum()
    {
        return $this->state([
            'name' => 'peo-coliseum',
        ]);
    }

    public function pho()
    {
        return $this->state([
            'name' => 'pho',
        ]);
    }

    public function pvo()
    {
        return $this->state([
            'name' => 'pvo',
        ]);
    }

    public function penro()
    {
        return $this->state([
            'name' => 'penro',
        ]);
    }

    public function ppdo()
    {
        return $this->state([
            'name' => 'ppdo',
        ]);
    }

    public function pgso_1()
    {
        return $this->state([
            'name' => 'pgso-1',
        ]);
    }

    public function pgso_2()
    {
        return $this->state([
            'name' => 'pgso-2',
        ]);
    }

    public function pgo_admin()
    {
        return $this->state([
            'name' => 'pgo-admin',
        ]);
    }

    public function pgo_pio()
    {
        return $this->state([
            'name' => 'pgo-pio',
        ]);
    }

    public function pgo_pdrrmo()
    {
        return $this->state([
            'name' => 'pgo-pdrrmo',
        ]);
    }

    public function pgo_bac()
    {
        return $this->state([
            'name' => 'pgo-bac',
        ]);
    }

    public function pgo_sbac()
    {
        return $this->state([
            'name' => 'pgo-sbac',
        ]);
    }

    public function pgo_osp()
    {
        return $this->state([
            'name' => 'pgo-osp',
        ]);
    }

    public function pgo_csu()
    {
        return $this->state([
            'name' => 'pgo-csu',
        ]);
    }
}
