<?php

namespace Database\Seeders;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Space;

class ReservationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Reservation::factory()->create([
            'space_id' => Space::inRandomOrder()->first()->id,
            'user_id' => User::where('role', 'client')->inRandomOrder()->first()->id,
            'event_name' => 'Birthday party',
            'start_date' => new Carbon('2024-7-02 09:00:00'),
            'end_date' => new Carbon('2024-7-02 12:00:00'),
        ]);
    }
}
