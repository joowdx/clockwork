<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Schedule;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        \App\Models\User::factory()->admin()->create();

        Schedule::create([
            'title' => 'default',
            'global' => true,
            'arrangement' => 'standard-work-hour',
            'days' => 'everyday',
            'start' => '2024-01-01',
            'end' => '2024-12-31',
            'timetable' => [
                'duration' => 8,
                'break' => 60,
                'p1' => '08:00',
                'p2' => '12:00',
                'p3' => '13:00',
                'p4' => '17:00',
            ],
            'threshold' => [
                'p1' => [
                    'min' => 280,
                    'max' => 180,
                ],
                'p2' => [
                    'min' => 180,
                    'max' => 120,
                ],
                'p3' => [
                    'min' => 120,
                    'max' => 180,
                ],
                'p4' => [
                    'min' => 180,
                    'max' => 360,
                ],
            ],
        ]);
    }
}
