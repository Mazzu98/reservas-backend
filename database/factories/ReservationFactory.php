<?php
namespace Database\Factories;

use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition()
    {
        return [
            'space_id' => $this->faker->numberBetween(1, 10),
            'user_id' => $this->faker->numberBetween(1, 10),
            'event_name' => $this->faker->word,
            'start_date' => $this->faker->time(),
            'end_date' => $this->faker->time(),
        ];
    }
}
