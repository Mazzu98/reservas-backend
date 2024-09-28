<?php

namespace Database\Seeders;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Reservation::factory()->create([
            'space_id' => 1,
            'user_id' => 1,
            'event_name' => 'Birthday party',
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addHours(3),
        ]);
    }
}
