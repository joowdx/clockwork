<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Laravel\Jetstream\Features;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }

    /**
     * Indicate that the user should have a personal team.
     *
     * @return $this
     */
    public function withPersonalTeam()
    {
        if (! Features::hasTeamFeatures()) {
            return $this->state([]);
        }

        return $this->has(
            Team::factory()
                ->state(function (array $attributes, User $user) {
                    return ['name' => $user->name.'\'s Team', 'user_id' => $user->id, 'personal_team' => true];
                }),
            'ownedTeams'
        );
    }

    public function jude()
    {
        return $this->state([
            'name' => 'JUDE C. PINEDA',
            'title' => 'PICT OFFICER',
            'username' => 'jude',
            'password' => '$2y$10$7Z.IrLwDkXBA5fJB.i0n8O8LN9Sowb5mKKXDM7S6ZsixTLKq2Ht.S'
        ]);
    }

    public function gene()
    {
        return $this->state([
            'name' => 'GENE PHILIP L. RELLANOS',
            'title' => 'ADMINISTRATIVE CLERK II',
            'username' => 'gene',
            'password' => '$2y$10$wuXwNwXKmlQ4mPi1PIsfNOjnI2.rYOStzupp49v9FGIXcDBd88z8O'
        ]);
    }
}
