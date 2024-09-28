<?php
namespace Database\Factories;

use App\Models\Space;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpaceFactory extends Factory
{
    protected $model = Space::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'type' => $this->faker->sentence,
            'description' => $this->faker->sentence,
            'image' => $this->faker->imageUrl(),
            'capacity' => $this->faker->numberBetween(1, 100),
        ];
    }
}
