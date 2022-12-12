<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => [
                'first' => $this->faker->firstName(),
                'last' => $this->faker->lastName(),
            ],
            'regular' => $this->faker->boolean(50),
        ];
    }

    public function jude()
    {
        return $this->state([
            'name' => [
                'first' => 'Jude',
                'middle' => 'Cabanilla',
                'last' => 'Pineda',
            ],
            'regular' => false,
            'office' => 'PGO-PICTO',
        ]);
    }

    public function gene()
    {
        return $this->state([
            'name' => [
                'first' => 'Gene Philip',
                'middle' => 'Largo',
                'last' => 'Rellanos',
            ],
            'regular' => true,
            'office' => 'PGO-PICTO',
        ]);
    }
}
