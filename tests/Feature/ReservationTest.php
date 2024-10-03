<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Space;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;

class ReservationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_reservation_cant_step_eachother(): void
    {

        DB::beginTransaction();

        $user = User::factory()->create(['role' => 'client']);
        $space = Space::factory()->create(['enable' => true]);

        $token = JWTAuth::fromUser($user);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/reservation', [
                'event_name' => 'Meeting',
                'space_id' => $space->id,
                'start_date' => '2024-7-02 10:00:00',
                'end_date' => '2024-7-02 11:00:00'
            ])
            ->assertStatus(201);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/reservation', [
                'event_name' => 'Meeting',
                'space_id' => $space->id,
                'start_date' => '2024-7-02 10:00:00',
                'end_date' => '2024-7-02 11:00:00'
            ])
            ->assertStatus(409);

        DB::rollBack();
    }

    public function test_only_authenticated_can_create_reservation(): void
    {

        DB::beginTransaction();

        $user = User::factory()->create(['role' => 'client']);
        $space = Space::factory()->create(['enable' => true]);

        $token = JWTAuth::fromUser($user);

        $this->postJson('/api/reservation', [
            'event_name' => 'Meeting',
            'space_id' => $space->id,
            'start_date' => '2024-7-02 10:00:00',
            'end_date' => '2024-7-02 11:00:00'
        ])
            ->assertStatus(401);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/reservation', [
                'event_name' => 'Meeting',
                'space_id' => $space->id,
                'start_date' => '2024-7-02 09:00:00',
                'end_date' => '2024-7-02 10:00:00'
            ])
            ->assertStatus(201);


        DB::rollBack();
    }

    public function test_available_spaces_are_filtered_correctly()
    {
        DB::beginTransaction();

        $user = User::factory()->create(['role' => 'client']);
        Space::factory()->create(['type' => 'strangeTypeToNotColision', 'capacity' => 20, 'enable' => true]);
        Space::factory()->create(['type' => 'strangeTypeToNotColision', 'capacity' => 50, 'enable' => true]);
        Space::factory()->create(['type' => 'office', 'capacity' => 5, 'enable' => true]);
        Space::factory()->create(['type' => 'office', 'capacity' => 23, 'enable' => true]);

        $token = JWTAuth::fromUser($user);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/space/search?type=strangeTypeToNotColis&capacity=20')
            ->assertStatus(200)
            ->assertJsonCount(2) // Solo un espacio debería ser devuelto.
            ->assertJsonFragment(['type' => 'strangeTypeToNotColision']);

        DB::rollBack();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
