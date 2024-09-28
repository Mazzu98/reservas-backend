<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Space;

class SpaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Space::factory()->create([
            'name' => 'Big saloon',
            'description' => 'A spacious and elegant venue for large gatherings and events.',
            'image' => 'big_space.jpg',
            'capacity' => 100,
        ]);

        Space::factory()->create([
            'name' => 'Small saloon',
            'description' => 'A cozy and intimate space for small gatherings and events.',
            'image' => 'small_space.jpg',
            'capacity' => 10,
        ]);

        Space::factory()->create([
            'name' => 'House with pool',
            'description' => 'A beautiful house with a swimming pool, perfect for relaxing and enjoying the outdoors.',
            'image' => 'house_pool.jpg',
            'capacity' => 25,
        ]);
    }
}
